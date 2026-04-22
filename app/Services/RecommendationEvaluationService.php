<?php

namespace App\Services;

use App\Models\RecommendationTrainingSample;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Offline hybrid recommender evaluation (thesis / SOP: recommendation accuracy with F1@K).
 *
 * What this is NOT:
 * - Not the same scoring function as live dashboard RecommendationService (different inputs:
 *   here we only have survey rows, not live Event models). This service mirrors the *idea*
 *   of hybrid scoring (content + collaborative + history) for Excel-backed evidence.
 *
 * What this IS:
 * - For each test user, rank fixed distance categories (e.g. 5km, 10km, …).
 * - Ground truth = their `race_category` from imported Excel.
 * - Metrics (per user, then averaged):
 *   - Precision@K: if hit, 1/K else 0 (single relevant item per row in this setup).
 *   - Recall@K: 1 if truth in top-K else 0.
 *   - F1@K: harmonic mean of the two above.
 *   - HitRate@K: same as recall here (one relevant label).
 *   - Accuracy@1: top-1 exact match.
 *
 * Typical flow:
 *   reco:import-excel → reco:evaluate or reco:grid-search
 */
class RecommendationEvaluationService
{
    /** Candidate distances always considered plus any distances seen in imported data. */
    private const DEFAULT_CANDIDATES = ['3km', '5km', '10km', '21km', '42km'];

    /**
     * Run train/test evaluation and return aggregate metrics (also persisted by Artisan commands).
     *
     * @param array{content:float,collab:float,history:float} $weights Relative emphasis; need not sum to 1 before call (commands normalize).
     * @return array<string,mixed> Metric bundle for DB JSON + console table
     */
    public function evaluate(array $weights, int $k = 3, string $split = 'time', float $testRatio = 0.2): array
    {
        $samples = RecommendationTrainingSample::query()
            ->where('research_consent', true)
            ->whereNotNull('race_category')
            ->get();

        if ($samples->count() < 20) {
            throw new \RuntimeException('Need at least 20 consented samples to run evaluation.');
        }

        [$train, $test] = $this->splitData($samples, $split, $testRatio);
        if ($train->isEmpty() || $test->isEmpty()) {
            throw new \RuntimeException('Split produced empty train/test set. Adjust test ratio.');
        }

        $candidateCategories = $this->buildCandidateCategories($samples);
        $k = max(1, min($k, count($candidateCategories)));

        $sumPrecision = 0.0;
        $sumRecall = 0.0;
        $sumF1 = 0.0;
        $hitsAt1 = 0;
        $hitsAtK = 0;

        foreach ($test as $sample) {
            $scores = [];
            foreach ($candidateCategories as $category) {
                $scores[$category] = $this->hybridScore($sample, $category, $train, $weights);
            }

            arsort($scores);
            $topK = array_slice(array_keys($scores), 0, $k);
            // Single-label ground truth per respondent (Excel "Race Category").
            $truth = $this->normalizeCategory((string) $sample->race_category);
            $hit = in_array($truth, $topK, true);

            // Standard top-K set recommendation metrics when exactly one relevant item exists.
            $precision = $hit ? (1.0 / $k) : 0.0;
            $recall = $hit ? 1.0 : 0.0;
            $f1 = ($precision + $recall) > 0
                ? (2 * $precision * $recall) / ($precision + $recall)
                : 0.0;

            $sumPrecision += $precision;
            $sumRecall += $recall;
            $sumF1 += $f1;
            $hitsAtK += $hit ? 1 : 0;
            $hitsAt1 += (($topK[0] ?? null) === $truth) ? 1 : 0;
        }

        $testCount = $test->count();

        return [
            'train_size' => $train->count(),
            'test_size' => $testCount,
            'k' => $k,
            'split' => $split,
            'test_ratio' => $testRatio,
            'weights' => $weights,
            'precision_at_k' => round($sumPrecision / $testCount, 4),
            'recall_at_k' => round($sumRecall / $testCount, 4),
            'f1_at_k' => round($sumF1 / $testCount, 4),
            'hit_rate_at_k' => round($hitsAtK / $testCount, 4),
            'accuracy_at_1' => round($hitsAt1 / $testCount, 4),
            'candidate_categories' => $candidateCategories,
        ];
    }

    private function splitData(Collection $samples, string $split, float $testRatio): array
    {
        $ratio = max(0.1, min($testRatio, 0.5));

        if ($split === 'random') {
            $ordered = $samples->shuffle();
        } else {
            // Thesis-friendly: older rows train, newer rows test (reduces "future leakage").
            $ordered = $samples->sortBy(fn ($s) => $s->observed_at?->timestamp ?? 0)->values();
        }

        $testSize = max(1, (int) round($ordered->count() * $ratio));
        $trainSize = max(1, $ordered->count() - $testSize);

        return [
            $ordered->take($trainSize)->values(),
            $ordered->slice($trainSize)->values(),
        ];
    }

    private function buildCandidateCategories(Collection $samples): array
    {
        $fromData = $samples->pluck('race_category')
            ->filter()
            ->map(fn ($c) => $this->normalizeCategory((string) $c))
            ->unique()
            ->values()
            ->all();

        $combined = array_values(array_unique(array_merge(self::DEFAULT_CANDIDATES, $fromData)));
        usort($combined, function (string $a, string $b): int {
            return $this->parseKm($a) <=> $this->parseKm($b);
        });

        return $combined;
    }

    /** Weighted sum of three hybrid channels; weights tuned via reco:evaluate / reco:grid-search. */
    private function hybridScore(RecommendationTrainingSample $sample, string $category, Collection $train, array $weights): float
    {
        $content = $this->contentScore($sample, $category);
        $collab = $this->collaborativeScore($sample, $category, $train);
        $history = $this->historyScore($sample, $category);

        return ($weights['content'] * $content)
            + ($weights['collab'] * $collab)
            + ($weights['history'] * $history);
    }

    /**
     * Content score in [0,1] from preference/safety heuristics.
     */
    private function contentScore(RecommendationTrainingSample $sample, string $category): float
    {
        $score = 0.0;
        $categoryKm = $this->parseKm($category);
        $experience = Str::lower((string) ($sample->running_experience ?? ''));
        $pace = (float) ($sample->exact_avg_pace_min_per_km ?? 0);
        $conditions = Str::lower((string) ($sample->health_conditions ?? ''));

        // Experience-based suitability
        if (str_contains($experience, 'beginner') || str_contains($experience, 'less than 1 year')) {
            $score += ($categoryKm <= 5) ? 0.45 : (($categoryKm <= 10) ? 0.2 : 0.0);
        } elseif (str_contains($experience, '1') || str_contains($experience, '3 years')) {
            $score += ($categoryKm <= 10) ? 0.45 : (($categoryKm <= 21) ? 0.25 : 0.1);
        } else {
            $score += ($categoryKm >= 10) ? 0.45 : 0.25;
        }

        // Pace suitability
        if ($pace > 0) {
            if ($pace <= 5.5 && $categoryKm >= 10) {
                $score += 0.25;
            } elseif ($pace > 5.5 && $pace <= 7.0 && $categoryKm <= 21) {
                $score += 0.25;
            } elseif ($pace > 7.0 && $categoryKm <= 10) {
                $score += 0.25;
            }
        }

        // Health safety penalty for long distances
        if ($conditions !== '' && $conditions !== 'none' && $categoryKm >= 21) {
            $score -= 0.35;
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * Collaborative score in [0,1] using similar sample groups.
     */
    private function collaborativeScore(RecommendationTrainingSample $sample, string $category, Collection $train): float
    {
        $gender = Str::lower((string) ($sample->gender ?? ''));
        $ageGroup = Str::lower((string) ($sample->age_group ?? ''));

        $similar = $train->filter(function (RecommendationTrainingSample $row) use ($gender, $ageGroup) {
            $sameGender = Str::lower((string) $row->gender) === $gender;
            $sameAgeGroup = Str::lower((string) $row->age_group) === $ageGroup;

            return $sameGender && $sameAgeGroup;
        });

        if ($similar->isEmpty()) {
            $similar = $train;
        }

        $target = $this->normalizeCategory($category);
        $matchCount = $similar->filter(function (RecommendationTrainingSample $row) use ($target) {
            return $this->normalizeCategory((string) $row->race_category) === $target;
        })->count();

        return $matchCount > 0 ? min(1.0, $matchCount / max(1, $similar->count())) : 0.0;
    }

    /**
     * History-style score in [0,1] from total event participation + club data.
     */
    private function historyScore(RecommendationTrainingSample $sample, string $category): float
    {
        $categoryKm = $this->parseKm($category);
        $eventsText = Str::lower((string) ($sample->total_events_joined ?? ''));
        $club = (bool) $sample->running_club_member;

        $historyLevel = 0;
        if (str_contains($eventsText, 'none')) {
            $historyLevel = 0;
        } elseif (str_contains($eventsText, '1') || str_contains($eventsText, '3')) {
            $historyLevel = 1;
        } elseif (str_contains($eventsText, '4') || str_contains($eventsText, '10')) {
            $historyLevel = 2;
        } elseif (str_contains($eventsText, 'more than 10')) {
            $historyLevel = 3;
        }

        $score = 0.0;
        if ($historyLevel === 0) {
            $score = ($categoryKm <= 5) ? 0.8 : 0.25;
        } elseif ($historyLevel === 1) {
            $score = ($categoryKm <= 10) ? 0.8 : 0.35;
        } elseif ($historyLevel === 2) {
            $score = ($categoryKm <= 21) ? 0.8 : 0.45;
        } else {
            $score = ($categoryKm >= 10) ? 0.8 : 0.5;
        }

        if ($club && $categoryKm >= 10) {
            $score += 0.1;
        }

        return min(1.0, $score);
    }

    private function normalizeCategory(string $value): string
    {
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*km/i', $value, $match)) {
            return rtrim(rtrim($match[1], '0'), '.') . 'km';
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $match)) {
            return rtrim(rtrim($match[1], '0'), '.') . 'km';
        }

        return Str::lower(trim($value));
    }

    private function parseKm(string $value): float
    {
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $match)) {
            return (float) $match[1];
        }

        return 0.0;
    }
}

