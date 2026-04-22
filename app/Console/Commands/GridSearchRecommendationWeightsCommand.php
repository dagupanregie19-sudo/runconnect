<?php

namespace App\Console\Commands;

use App\Models\RecommendationExperimentRun;
use App\Services\RecommendationEvaluationService;
use Illuminate\Console\Command;

/**
 * Step 3 (optional): try several hybrid weight triples and compare F1@K (SOP: tuning / defense).
 *
 * Objective alignment:
 * - Automates "which content/collab/history mix works best on this dataset" for thesis.
 * - Each combo is saved as its own row in `recommendation_experiment_runs`.
 *
 * Prerequisites:
 *   php artisan reco:import-excel --truncate
 *
 * Example:
 *   php artisan reco:grid-search --k=3 --split=time --test-ratio=0.2 --name-prefix=panel
 *
 * Tip for defense: cite the run_id with highest F1@K and paste weights + metrics into Chapter 4.
 *
 * @see EvaluateRecommendationModelCommand (single run with explicit weights)
 */
class GridSearchRecommendationWeightsCommand extends Command
{
    protected $signature = 'reco:grid-search
                            {--k=3 : Top-K cutoff}
                            {--split=time : Split strategy: time or random}
                            {--test-ratio=0.2 : Fraction of samples used for test set}
                            {--name-prefix=grid : Prefix for experiment run names}';

    protected $description = 'Run weight grid search and pick best F1@K configuration';

    public function handle(RecommendationEvaluationService $evaluationService): int
    {
        $k = (int) $this->option('k');
        $split = (string) $this->option('split');
        $testRatio = (float) $this->option('test-ratio');
        $namePrefix = (string) $this->option('name-prefix');

        // Preset grid: extend this array if panel asks for more weight combinations.
        $weightGrid = [
            ['content' => 0.70, 'collab' => 0.30, 'history' => 0.00],
            ['content' => 0.60, 'collab' => 0.30, 'history' => 0.10],
            ['content' => 0.50, 'collab' => 0.30, 'history' => 0.20],
            ['content' => 0.50, 'collab' => 0.50, 'history' => 0.00],
            ['content' => 0.40, 'collab' => 0.40, 'history' => 0.20],
        ];

        $rows = [];
        $best = null;

        foreach ($weightGrid as $idx => $weights) {
            try {
                $metrics = $evaluationService->evaluate($weights, $k, $split, $testRatio);
            } catch (\Throwable $e) {
                $this->error("Grid search failed at row {$idx}: {$e->getMessage()}");
                return self::FAILURE;
            }

            $run = RecommendationExperimentRun::create([
                'name' => "{$namePrefix}_" . now()->format('Ymd_His') . "_{$idx}",
                'k' => $metrics['k'],
                'split_strategy' => $metrics['split'],
                'test_ratio' => $metrics['test_ratio'],
                'weights' => $weights,
                'train_size' => $metrics['train_size'],
                'test_size' => $metrics['test_size'],
                'metrics' => [
                    'precision_at_k' => $metrics['precision_at_k'],
                    'recall_at_k' => $metrics['recall_at_k'],
                    'f1_at_k' => $metrics['f1_at_k'],
                    'hit_rate_at_k' => $metrics['hit_rate_at_k'],
                    'accuracy_at_1' => $metrics['accuracy_at_1'],
                    'candidate_categories' => $metrics['candidate_categories'],
                ],
            ]);

            $rows[] = [
                $run->id,
                json_encode($weights),
                $metrics['precision_at_k'],
                $metrics['recall_at_k'],
                $metrics['f1_at_k'],
                $metrics['accuracy_at_1'],
            ];

            if ($best === null || $metrics['f1_at_k'] > $best['f1_at_k']) {
                $best = [
                    'run_id' => $run->id,
                    'weights' => $weights,
                    'f1_at_k' => $metrics['f1_at_k'],
                    'precision_at_k' => $metrics['precision_at_k'],
                    'recall_at_k' => $metrics['recall_at_k'],
                    'accuracy_at_1' => $metrics['accuracy_at_1'],
                ];
            }
        }

        $this->table(
            ['Run ID', 'Weights', 'Precision@K', 'Recall@K', 'F1@K', 'Accuracy@1'],
            $rows
        );

        if ($best !== null) {
            $this->newLine();
            $this->info('Best configuration by F1@K:');
            $this->table(
                ['Run ID', 'Weights', 'Precision@K', 'Recall@K', 'F1@K', 'Accuracy@1'],
                [[
                    $best['run_id'],
                    json_encode($best['weights']),
                    $best['precision_at_k'],
                    $best['recall_at_k'],
                    $best['f1_at_k'],
                    $best['accuracy_at_1'],
                ]]
            );
        }

        return self::SUCCESS;
    }
}

