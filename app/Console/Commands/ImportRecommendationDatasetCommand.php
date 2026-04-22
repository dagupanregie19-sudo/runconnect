<?php

namespace App\Console\Commands;

use App\Services\RecommendationDatasetImportService;
use Illuminate\Console\Command;

/**
 * Step 1 of the offline evaluation pipeline (SOP: hybrid recommendation + evidence).
 *
 * Objective alignment:
 * - Loads survey Excel into `recommendation_training_samples` so you can defend
 *   "data-driven" evaluation (Chapter 4 / methodology).
 *
 * How to use (from project root):
 *   php artisan migrate
 *   php artisan reco:import-excel --truncate
 *
 * Default file: docx/RunConnect1.xlsx → sheet "Cleaned Data".
 * Rows without Research Consent=Yes or without a parseable Race Category are skipped.
 *
 * @see RecommendationDatasetImportService
 * @see EvaluateRecommendationModelCommand (step 2: metrics)
 */
class ImportRecommendationDatasetCommand extends Command
{
    protected $signature = 'reco:import-excel 
                            {path=docx/RunConnect1.xlsx : Relative or absolute path to Excel workbook}
                            {--truncate : Clear existing training samples before import}';

    protected $description = 'Import recommendation training samples from Excel workbook';

    public function handle(RecommendationDatasetImportService $importService): int
    {
        $inputPath = (string) $this->argument('path');
        $path = $this->resolvePath($inputPath);

        $this->info("Importing dataset from: {$path}");
        $result = $importService->importFromExcel($path, (bool) $this->option('truncate'));

        $this->newLine();
        $this->line('Sheet: ' . $result['sheet']);
        $this->line('Inserted rows: ' . $result['inserted']);
        $this->line('Skipped rows: ' . $result['skipped']);
        $this->info('Recommendation training dataset import complete.');

        return self::SUCCESS;
    }

    /** Resolve path relative to Laravel base_path() unless already absolute. */
    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}

