<?php

namespace App\Console\Commands;

use App\Models\RecommendationExperimentRun;
use App\Services\RecommendationEvaluationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Step 2: single-run hybrid recommendation evaluation (SOP: accuracy / F1 defense).
 *
 * Objective alignment:
 * - Produces Precision@K, Recall@K, F1@K, HitRate@K, Accuracy@1 for panel evidence.
 * - Does NOT "train" F1; it scores candidates and measures overlap with each user's
 *   stated race_category (ground truth from Excel).
 *
 * Prerequisites:
 *   php artisan reco:import-excel --truncate
 *
 * Example:
 *   php artisan reco:evaluate --k=3 --weights=0.5,0.3,0.2 --split=time --test-ratio=0.2 --name=baseline
 *
 * --weights = content,collab,history (normalized to sum 1.0). Tuning these is how you
 * align the offline experiment with your thesis "hybrid" narrative.
 *
 * Results persist in `recommendation_experiment_runs` (JSON metrics + weights).
 *
 * @see RecommendationEvaluationService
 * @see GridSearchRecommendationWeightsCommand (automated weight sweep)
 */
class EvaluateRecommendationModelCommand extends Command
{
    protected $signature = 'reco:evaluate
                            {--k=3 : Top-K cutoff}
                            {--weights=0.5,0.3,0.2 : content,collab,history weights}
                            {--split=time : Split strategy: time or random}
                            {--test-ratio=0.2 : Fraction of samples used for test set}
                            {--name= : Custom run name}
                            {--notes= : Optional notes for the saved run}';

    protected $description = 'Evaluate hybrid recommendation with Precision/Recall/F1@K';

    public function handle(RecommendationEvaluationService $evaluationService): int
    {
        $k = (int) $this->option('k');
        $split = Str::lower((string) $this->option('split'));
        $testRatio = (float) $this->option('test-ratio');
        try {
            $weights = $this->parseWeights((string) $this->option('weights'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!in_array($split, ['time', 'random'], true)) {
            $this->error('Invalid --split value. Use "time" or "random".');
            return self::FAILURE;
        }

        try {
            $metrics = $evaluationService->evaluate($weights, $k, $split, $testRatio);
        } catch (\Throwable $e) {
            $this->error('Evaluation failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: ('eval_' . now()->format('Ymd_His')));
        $run = RecommendationExperimentRun::create([
            'name' => $name,
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
            'notes' => (string) ($this->option('notes') ?? ''),
        ]);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Run ID', $run->id],
                ['Run Name', $run->name],
                ['Train Size', $metrics['train_size']],
                ['Test Size', $metrics['test_size']],
                ['K', $metrics['k']],
                ['Weights', json_encode($weights)],
                ['Precision@K', $metrics['precision_at_k']],
                ['Recall@K', $metrics['recall_at_k']],
                ['F1@K', $metrics['f1_at_k']],
                ['HitRate@K', $metrics['hit_rate_at_k']],
                ['Accuracy@1', $metrics['accuracy_at_1']],
            ]
        );

        $this->info('Evaluation complete and saved to recommendation_experiment_runs.');

        return self::SUCCESS;
    }

    /**
     * Parse "a,b,c" into normalized {content, collab, history} for hybridScore().
     *
     * @return array{content:float,collab:float,history:float}
     */
    private function parseWeights(string $weightInput): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $weightInput)), fn ($v) => $v !== ''));
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Weights must be in format content,collab,history');
        }

        $content = (float) $parts[0];
        $collab = (float) $parts[1];
        $history = (float) $parts[2];
        $sum = $content + $collab + $history;

        if ($sum <= 0) {
            throw new \InvalidArgumentException('Weight sum must be greater than zero.');
        }

        return [
            'content' => $content / $sum,
            'collab' => $collab / $sum,
            'history' => $history / $sum,
        ];
    }
}

