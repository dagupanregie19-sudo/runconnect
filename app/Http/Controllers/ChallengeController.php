<?php

namespace App\Http\Controllers;

use App\Models\ChallengeLog;
use App\Models\UserChallenge;
use App\Services\PaceCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChallengeController extends Controller
{
    /**
     * Start a new challenge (or restart at a specific level).
     */
    public function start(Request $request)
    {
        $user = Auth::user();

        // Check if user already has an active challenge
        $active = UserChallenge::where('user_id', $user->id)->where('status', 'active')->first();
        if ($active) {
            return back()->with('error', 'You already have an active challenge.');
        }

        // Determine level: use runner profile fitness level, or default to beginner
        $level = $request->input('level');
        if (!$level || !isset(UserChallenge::LEVELS[$level])) {
            $profile = $user->runnerProfile;
            $level = $profile ? $profile->fitness_level : 'beginner';
        }

        $config = UserChallenge::LEVELS[$level];

        UserChallenge::create([
            'user_id' => $user->id,
            'level' => $level,
            'status' => 'active',
            'target_distance' => $config['target_distance'],
            'distance_logged' => 0,
            'daily_target' => $config['daily_target'],
            'duration_days' => $config['duration_days'],
            'started_at' => now(),
            'deadline_at' => now()->addDays($config['duration_days']),
        ]);

        return back()->with('success', "Challenge started! Complete the {$config['label']} challenge.");
    }

    /**
     * Log a run for the active challenge.
     */
    public function logRun(Request $request)
    {
        $user = Auth::user();

        $challenge = UserChallenge::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$challenge) {
            return back()->with('error', 'No active challenge found.');
        }

        // Check if expired
        if ($challenge->is_expired) {
            $this->failChallenge($challenge);
            return back()->with('error', 'Your challenge has expired! You have been moved accordingly.');
        }

        $request->validate([
            'distance_km' => 'required|numeric|min:0.01|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'route_data' => 'nullable|json',
        ]);

        $distance = round($request->distance_km, 2);
        $duration = $request->duration_seconds;
        $routePoints = $request->route_data ? json_decode($request->route_data, true) : null;
        $startCoords = $routePoints ? ($routePoints[0] ?? null) : null;
        $endCoords = $routePoints ? (end($routePoints) ?: null) : null;

        ChallengeLog::create([
            'user_challenge_id' => $challenge->id,
            'distance_km' => $distance,
            'duration_seconds' => $duration,
            'route_points' => $routePoints,
            'start_coords' => $startCoords,
            'end_coords' => $endCoords,
            'logged_date' => today()->toDateString(),
        ]);

        // Update total
        $challenge->distance_logged = (float) $challenge->distance_logged + $distance;
        $challenge->save();

        // Check if challenge is completed
        if ($challenge->distance_logged >= $challenge->target_distance) {
            $isComplete = false;
            // For daily challenges, also check days completed
            if ($challenge->daily_target) {
                // days_completed is a dynamic property, let's just make sure it's met
                if ($challenge->days_completed >= $challenge->duration_days) {
                    $isComplete = true;
                }
            } else {
                $isComplete = true;
            }

            if ($isComplete) {
                $challenge->status = 'completed';
                $challenge->completed_at = now();
                $challenge->save();

                // --- Intelligent Pace Calculation Engine ---
                $totalDuration = ChallengeLog::where('user_challenge_id', $challenge->id)->sum('duration_seconds');
                if ($totalDuration > 0 && $user->runnerProfile) {
                    $pace = PaceCalculatorService::calculatePace((float) $challenge->distance_logged, (int) $totalDuration);
                    $newFitness = PaceCalculatorService::determineFitnessLevel(
                        $pace,
                        $user->runnerProfile->age ?? 25,
                        $user->runnerProfile->gender ?? 'male'
                    );
                    $user->runnerProfile->update([
                        'verified_pace' => round($pace, 2),
                        'fitness_level' => $newFitness
                    ]);
                }
                // -------------------------------------------

                return back()->with('challenge_complete', true);
            }
        }

        return back()->with('success', "Logged {$distance}km! Keep going! 🏃");
    }

    /**
     * Advance to the next level after completion.
     */
    public function advance()
    {
        $user = Auth::user();

        $challenge = UserChallenge::where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$challenge) {
            return back()->with('error', 'No completed challenge to advance from.');
        }

        $config = UserChallenge::LEVELS[$challenge->level];
        // Now rely on the newly calculated and mathematically verified fitness level
        $nextLevel = $user->runnerProfile ? $user->runnerProfile->fitness_level : $config['on_success'];

        // Start the new challenge
        $nextConfig = UserChallenge::LEVELS[$nextLevel];
        UserChallenge::create([
            'user_id' => $user->id,
            'level' => $nextLevel,
            'status' => 'active',
            'target_distance' => $nextConfig['target_distance'],
            'distance_logged' => 0,
            'daily_target' => $nextConfig['daily_target'],
            'duration_days' => $nextConfig['duration_days'],
            'started_at' => now(),
            'deadline_at' => now()->addDays($nextConfig['duration_days']),
        ]);

        return back()->with('success', "Congratulations! You've advanced to {$nextConfig['label']}! 🎉");
    }

    /**
     * Redo the same level challenge.
     */
    public function redo()
    {
        $user = Auth::user();

        $challenge = UserChallenge::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'failed'])
            ->latest()
            ->first();

        if (!$challenge) {
            return back()->with('error', 'No challenge to redo.');
        }

        $level = $challenge->level;

        // If it was a failure, use the failure level
        if ($challenge->status === 'failed') {
            $config = UserChallenge::LEVELS[$challenge->level];
            $level = $config['on_failure'];
        }

        $config = UserChallenge::LEVELS[$level];

        // Update runner profile fitness level
        if ($user->runnerProfile) {
            $user->runnerProfile->update(['fitness_level' => $level]);
        }

        UserChallenge::create([
            'user_id' => $user->id,
            'level' => $level,
            'status' => 'active',
            'target_distance' => $config['target_distance'],
            'distance_logged' => 0,
            'daily_target' => $config['daily_target'],
            'duration_days' => $config['duration_days'],
            'started_at' => now(),
            'deadline_at' => now()->addDays($config['duration_days']),
        ]);

        return back()->with('success', "Challenge restarted at {$config['label']} level. Let's go! 💪");
    }

    /**
     * Check and handle expired challenges.
     */
    public function checkExpiry()
    {
        $user = Auth::user();

        $challenge = UserChallenge::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($challenge && $challenge->is_expired) {
            $this->failChallenge($challenge);
            return back()->with('challenge_failed', true);
        }

        return back();
    }

    /**
     * Handle challenge failure.
     */
    private function failChallenge(UserChallenge $challenge)
    {
        $challenge->status = 'failed';
        $challenge->save();

        $config = UserChallenge::LEVELS[$challenge->level];
        $failLevel = $config['on_failure'];

        // Update runner profile fitness level
        $user = $challenge->user;
        if ($user->runnerProfile) {
            $user->runnerProfile->update(['fitness_level' => $failLevel]);
        }
    }
}
