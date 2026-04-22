<?php

namespace App\Services;

class PaceCalculatorService
{
    /**
     * Calculate pace in minutes per kilometer.
     */
    public static function calculatePace(float $distanceKm, int $timeSeconds): float
    {
        if ($distanceKm <= 0) {
            return 0;
        }

        $timeMinutes = $timeSeconds / 60;
        return $timeMinutes / $distanceKm;
    }

    /**
     * Determine fitness level mathematically using Age and Gender offset parameters.
     * This mimics ML-based performance verification against demographic benchmarks.
     * 
     * @param float  $paceMinKm
     * @param int    $age
     * @param string $gender ('male', 'female', 'other')
     * @return string (beginner, improving, intermediate)
     */
    public static function determineFitnessLevel(float $paceMinKm, int $age, string $gender): string
    {
        if ($paceMinKm <= 0) {
            return 'beginner'; // Failsafe
        }

        // Base Benchmark Thresholds for a standard 20-29 Male (min/km)
        // Adjust these to reflect RunningPace.io averages.
        $baseThresholds = [
            'intermediate' => 6.50, // Sub-6:30 min/km
            'improving' => 7.50, // Sub-7:30 min/km
        ];

        // 1. Gender Offset (Women generally have a slightly higher physiological average pace)
        $gender = strtolower($gender);
        $genderOffset = 0.0;
        if ($gender === 'female') {
            $genderOffset = 0.60; // Adds ~36 seconds per km to threshold
        } elseif ($gender === 'other') {
            $genderOffset = 0.30;
        }

        // 2. Age Offset (Pace potential degrades naturally after age 30)
        $ageOffset = 0.0;
        if ($age < 20) {
            $ageOffset = 0.20; // Teens slightly slower than prime 20-29
        } elseif ($age >= 30 && $age <= 39) {
            $ageOffset = 0.20;
        } elseif ($age >= 40 && $age <= 49) {
            $ageOffset = 0.50; // Adds ~30s
        } elseif ($age >= 50 && $age <= 59) {
            $ageOffset = 1.00; // Adds 1min
        } elseif ($age >= 60) {
            $ageOffset = 1.50; // Adds 1min 30s
        }

        // Apply Offsets to determine final dynamic thresholds
        $tIntermediate = $baseThresholds['intermediate'] + $genderOffset + $ageOffset;
        $tImproving = $baseThresholds['improving'] + $genderOffset + $ageOffset;

        // Categorize based on evaluated thresholds
        if ($paceMinKm <= $tIntermediate) {
            return 'intermediate';
        }
        if ($paceMinKm <= $tImproving) {
            return 'improving';
        }

        // Anything slower than improving base translates to beginner
        return 'beginner';
    }
}
