<?php

namespace App\Services;

/**
 * FitnessLevelCalculator
 * 
 * Automatically assigns a runner's initial fitness level based on age and gender
 * using demographic pace benchmarks from RunningPace.io (referenced in thesis [25]).
 * 
 * Instead of subjective self-assessment, the system uses statistical baselines
 * of average 5K finishing times by age group and gender to determine what
 * fitness level a new runner should start at. This aligns with the thesis
 * Conceptual Framework: "Predictive Analytics Module uses a classification
 * algorithm to match user profiles against historical patterns."
 * 
 * The pace benchmarks (min/km) define what an average runner in each demographic
 * can achieve. Younger and male runners statistically have faster baselines,
 * so they start at higher default levels, while older runners or those with
 * health conditions start at safer, lower levels.
 * 
 * Fitness Level Mapping:
 *   - intermediate: Pace < 6:15/km
 *   - improving   : Pace 6:15-7:30/km
 *   - beginner    : Pace > 7:30/km
 */
class FitnessLevelCalculator
{
    /**
     * Average 5K pace benchmarks in minutes per km, by age group and gender.
     * Data source: RunningPace.io (2025) & Healthline aggregated race data.
     * 
     * These represent the AVERAGE recreational runner's pace for each demographic.
     * The system uses these baselines to determine a reasonable starting fitness level.
     */
    private static array $paceBenchmarks = [
        // [age_min, age_max] => ['male' => pace_min_per_km, 'female' => pace_min_per_km]
        // Pace is in decimal minutes per km (e.g., 5.93 = 5:56/km)
        'male' => [
            [10, 15, 6.50],  // Youth: ~6:30/km avg
            [16, 19, 5.93],  // Teen: ~5:56/km (9:34/mi)
            [20, 24, 5.90],  // Young adult: ~5:54/km (9:30/mi)
            [25, 29, 6.24],  // ~6:14/km (10:03/mi)
            [30, 34, 6.30],  // ~6:18/km (10:09/mi)
            [35, 39, 6.76],  // ~6:45/km (10:53/mi)
            [40, 44, 6.50],  // ~6:30/km (10:28/mi)
            [45, 49, 6.65],  // ~6:39/km (10:43/mi)
            [50, 54, 6.91],  // ~6:55/km (11:08/mi)
            [55, 59, 7.54],  // ~7:32/km (12:08/mi)
            [60, 64, 8.12],  // ~8:07/km (13:05/mi)
            [65, 120, 8.61], // ~8:37/km (13:52/mi)
        ],
        'female' => [
            [10, 15, 7.80],  // Youth: ~7:48/km avg
            [16, 19, 7.55],  // Teen: ~7:33/km (12:09/mi)
            [20, 24, 7.29],  // ~7:17/km (11:44/mi)
            [25, 29, 7.27],  // ~7:16/km (11:42/mi)
            [30, 34, 7.76],  // ~7:45/km (12:29/mi)
            [35, 39, 7.49],  // ~7:29/km (12:03/mi)
            [40, 44, 7.71],  // ~7:42/km (12:24/mi)
            [45, 49, 7.88],  // ~7:53/km (12:41/mi)
            [50, 54, 8.28],  // ~8:17/km (13:20/mi)
            [55, 59, 9.09],  // ~9:05/km (14:37/mi)
            [60, 64, 9.19],  // ~9:11/km (14:47/mi)
            [65, 120, 10.07], // ~10:04/km (16:12/mi)
        ],
    ];

    /**
     * Fitness level thresholds based on pace (min/km).
     * These thresholds determine the category boundaries.
     */
    private static array $levelThresholds = [
        // [max_pace, level] — checked in order, first match wins
        ['max' => 6.25, 'level' => 'intermediate'],   // Under 6:15/km
        ['max' => 7.50, 'level' => 'improving'],      // 6:15 - 7:30/km
        ['max' => PHP_FLOAT_MAX, 'level' => 'beginner'], // Over 7:30/km
    ];

    /**
     * Calculate the fitness level for a runner based on their demographics.
     *
     * @param int    $age    Runner's age
     * @param string $gender Runner's gender (male, female, other)
     * @param array  $healthConditions Optional health conditions
     * @param float|null $selfReportedPace Optional user-provided average pace
     * @return array ['level' => string, 'estimated_pace' => float, 'reasoning' => string]
     */
    public static function calculate(int $age, string $gender, array $healthConditions = [], ?float $selfReportedPace = null): array
    {
        // Normalize gender — treat 'other' as average of male/female
        $genderKey = in_array(strtolower($gender), ['male', 'female']) ? strtolower($gender) : null;

        // Get base pace from demographic benchmarks
        if ($genderKey) {
            $basePace = self::getPaceForAge($genderKey, $age);
        } else {
            // For 'other' gender, average of male and female benchmarks
            $malePace = self::getPaceForAge('male', $age);
            $femalePace = self::getPaceForAge('female', $age);
            $basePace = ($malePace + $femalePace) / 2;
        }

        // Apply health condition modifiers
        // Certain conditions warrant a more conservative (slower) estimate
        $modifier = self::getHealthModifier($healthConditions);
        $adjustedDemographicPace = $basePace * $modifier;

        // If self-reported pace is provided, blend it with the demographic estimate
        // to balance their self-assessment against statistical reality.
        if ($selfReportedPace !== null && $selfReportedPace > 0) {
            // Weighting: 70% self-report, 30% demographic baseline
            $adjustedSelfPace = $selfReportedPace * $modifier; // also apply health safety
            $adjustedPace = ($adjustedSelfPace * 0.70) + ($adjustedDemographicPace * 0.30);
        } else {
            $adjustedPace = $adjustedDemographicPace;
        }

        // Determine fitness level from adjusted pace
        $level = self::paceToLevel($adjustedPace);

        // Build reasoning string
        $reasoning = sprintf(
            'Based on age %d (%s), estimated average pace is %.1f min/km.',
            $age,
            $genderKey ?? 'other',
            $basePace
        );

        if ($modifier > 1.0) {
            $reasoning .= sprintf(
                ' Health safety modifier applied (%s).',
                implode(', ', array_filter($healthConditions, fn($c) => $c !== 'None'))
            );
        }
        
        if ($selfReportedPace !== null && $selfReportedPace > 0) {
            $reasoning .= sprintf(' Blended with self-reported pacing of %.1f min/km.', $selfReportedPace);
        }

        $reasoning .= sprintf(' Assigned level: %s.', ucfirst($level));

        return [
            'level' => $level,
            'estimated_pace' => round($adjustedPace, 2),
            'reasoning' => $reasoning,
        ];
    }

    /**
     * Look up the average pace for a given gender and age.
     */
    private static function getPaceForAge(string $gender, int $age): float
    {
        $benchmarks = self::$paceBenchmarks[$gender] ?? self::$paceBenchmarks['male'];

        foreach ($benchmarks as [$minAge, $maxAge, $pace]) {
            if ($age >= $minAge && $age <= $maxAge) {
                return $pace;
            }
        }

        // Fallback for extreme ages
        if ($age < 10) {
            return 8.00; // Very young, conservative estimate
        }

        return 9.00; // Very old, conservative estimate
    }

    /**
     * Convert pace to fitness level.
     */
    private static function paceToLevel(float $pace): string
    {
        foreach (self::$levelThresholds as $threshold) {
            if ($pace <= $threshold['max']) {
                return $threshold['level'];
            }
        }

        return 'beginner';
    }

    /**
     * Get a pace modification factor based on health conditions.
     * Returns a multiplier > 1.0 for conditions that reduce performance.
     */
    private static function getHealthModifier(array $conditions): float
    {
        if (empty($conditions) || in_array('None', $conditions)) {
            return 1.0;
        }

        $modifier = 1.0;

        // Each condition adds a small penalty that effectively pushes the
        // estimated pace slower, resulting in a more conservative fitness level
        $conditionModifiers = [
            'Asthma'              => 1.10,  // +10% slower
            'Heart Condition'     => 1.25,  // +25% slower — safety priority
            'High Blood Pressure' => 1.15,  // +15% slower
            'Joint Problems'      => 1.15,  // +15% slower
            'Diabetes'            => 1.10,  // +10% slower
            'Recent Injury'       => 1.20,  // +20% slower
        ];

        foreach ($conditions as $condition) {
            // Skip "None" and "Other: ..." entries by matching known conditions
            foreach ($conditionModifiers as $name => $mod) {
                if (stripos($condition, $name) !== false) {
                    // Use max modifier if multiple conditions overlap
                    $modifier = max($modifier, $mod);
                }
            }
        }

        // If they have 3+ conditions, apply compound penalty
        $activeConditions = array_filter($conditions, fn($c) => $c !== 'None');
        if (count($activeConditions) >= 3) {
            $modifier *= 1.05; // Extra 5% for multiple conditions
        }

        return $modifier;
    }
}
