<?php

namespace App\Services;

use App\Models\RecommendationTrainingSample;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel → database bridge for offline recommendation evaluation.
 *
 * SOP / thesis role:
 * - Turns RunConnect survey rows into structured rows the evaluator can query.
 * - "Race Category" becomes the recommendation ground truth (what distance bucket the respondent prefers).
 *
 * Expected workbook: docx/RunConnect1.xlsx
 * - Preferred sheet name: "Cleaned Data" (falls back to first sheet).
 *
 * Invoked by: php artisan reco:import-excel
 */
class RecommendationDatasetImportService
{
    /**
     * Import cleaned survey rows from the Excel workbook into recommendation_training_samples.
     *
     * @param bool $truncate When true, wipes the table first (use for reproducible re-imports).
     * @return array{inserted:int, skipped:int, sheet:string}
     */
    public function importFromExcel(string $excelPath, bool $truncate = false): array
    {
        if (!is_file($excelPath)) {
            throw new \InvalidArgumentException("Excel file not found: {$excelPath}");
        }

        if ($truncate) {
            RecommendationTrainingSample::query()->truncate();
        }

        $spreadsheet = IOFactory::load($excelPath);
        // Match thesis dataset tab name from RunConnect1.xlsx
        $sheet = $spreadsheet->getSheetByName('Cleaned Data') ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            return ['inserted' => 0, 'skipped' => 0, 'sheet' => $sheet->getTitle()];
        }

        $headerRow = array_shift($rows);
        $headers = $this->normalizeHeaders($headerRow);

        $inserted = 0;
        $skipped = 0;
        $sourceRow = 1;

        foreach ($rows as $row) {
            $sourceRow++;
            $data = $this->mapRow($headers, $row, $sourceRow);

            if ($data === null) {
                $skipped++;
                continue;
            }

            RecommendationTrainingSample::create($data);
            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'sheet' => $sheet->getTitle(),
        ];
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $column => $value) {
            $headers[$column] = Str::of((string) $value)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
        }

        return $headers;
    }

    private function mapRow(array $headers, array $row, int $sourceRow): ?array
    {
        $record = [];
        foreach ($headers as $column => $key) {
            $record[$key] = $row[$column] ?? null;
        }

        // Ethics / SOP: only rows that explicitly consented to research use.
        $consent = $this->toBool($record['research_consent'] ?? null, true);
        if (!$consent) {
            return null;
        }

        // Ground truth for evaluation: must normalize to a single distance label (e.g. "5km", "42km").
        $raceCategory = $this->normalizeRaceCategory($record['race_category'] ?? null);
        if ($raceCategory === null) {
            return null;
        }

        return [
            'source_row' => $sourceRow,
            'observed_at' => $this->parseTimestamp($record['timestamp'] ?? null),
            'full_name' => $this->toNullableString($record['full_name'] ?? null),
            'age_group' => $this->toNullableString($record['age_group'] ?? null),
            'gender' => $this->toNullableString($record['gender'] ?? null),
            'running_experience' => $this->toNullableString($record['running_experience'] ?? null),
            'race_category' => $raceCategory,
            'total_events_joined' => $this->toNullableString($record['total_events_joined'] ?? null),
            'running_club_member' => $this->toBool($record['running_club_member'] ?? null, false),
            'average_pace_category' => $this->toNullableString($record['average_pace_category'] ?? null),
            'exact_avg_pace_min_per_km' => $this->toNullableFloat($record['exact_avg_pace_min_km'] ?? $record['exact_avg_pace_min_per_km'] ?? null),
            'best_5km_time' => $this->toNullableString($record['best_5km_time'] ?? null),
            'health_conditions' => $this->toNullableString($record['health_conditions'] ?? null),
            'research_consent' => $consent,
        ];
    }

    private function parseTimestamp($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value, 'Asia/Manila');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeRaceCategory($value): ?string
    {
        $text = Str::of((string) $value)->lower()->trim()->toString();
        if ($text === '') {
            return null;
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*km/', $text, $match)) {
            return rtrim(rtrim($match[1], '0'), '.') . 'km';
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $text, $match)) {
            return rtrim(rtrim($match[1], '0'), '.') . 'km';
        }

        return null;
    }

    private function toBool($value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $v = Str::of((string) $value)->lower()->trim()->toString();

        return in_array($v, ['yes', 'y', 'true', '1'], true);
    }

    private function toNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        return $s;
    }

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string) $value, $match)) {
            return (float) $match[1];
        }

        return null;
    }
}

