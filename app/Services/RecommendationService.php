<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Support\Collection;

class RecommendationService
{
    private const EVENT_TIMEZONE = 'Asia/Manila';

    /**
     * Get Hybrid Recommendations for a runner.
     *
     * Strategy:
     *  - Runners WITHOUT health conditions: See ALL available events, scored and ranked
     *    by the Hybrid Algorithm (Content-Based + Collaborative Filtering). Best match
     *    floats to the top naturally.
     *  - Runners WITH health conditions: Hard safety firewall — only events matching their
     *    exact fitness_level difficulty are shown, still ranked by algorithm within that tier.
     *
     * Relevance Score breakdown (max ~100 pts):
     *   Difficulty Match    : 30 pts (exact), 15 pts (±1 rank), 0 pts (2+ ranks apart)
     *   Location Match      : 20 pts (runner's city found in event venue)
     *   Demographic Pop.    : up to 50 pts (similar age ±5 yrs & gender who registered)
     *   Cold-Start Boost    : +15 pts for brand-new events with 0 registrations
     */
    public static function getRecommendations(User $user, $registeredEventIds = []): Collection
    {
        $profile = $user->runnerProfile;

        // Base Query: future events not yet registered by this user
        $query = Event::with('organizer')
            ->withCount([
                'registrations as registered_count' => function ($q) {
                    $q->where('status', 'registered');
                },
            ])
            ->where('status', 'upcoming')
            ->whereDate('registration_end', '>=', now(self::EVENT_TIMEZONE)->toDateString());

        if (!empty($registeredEventIds)) {
            $query->whereNotIn('id', $registeredEventIds);
        }

        $events = $query->get();

        // No profile yet — fall back to chronological
        if (!$profile) {
            return $events->sortByDesc('created_at')->values();
        }

        // ── Health Safety Constraint ────────────────────────────────────────────────
        // Detect a real health condition (ignore when the only value is "None")
        $conditions = $profile->health_conditions ?? [];
        $hasHealthConditions = !empty($conditions)
            && !(count($conditions) === 1 && strtolower($conditions[0]) === 'none');

        $safeDifficulty = ucfirst($profile->fitness_level ?? 'beginner');

        if ($hasHealthConditions) {
            // Medical firewall: only events at the runner's exact safe tier
            $events = $events->filter(fn ($e) => $e->difficulty === $safeDifficulty);
        }
        // Healthy runners keep ALL events; the algorithm will rank them by fit.

        // ── Hybrid Recommendation Scoring ───────────────────────────────────────────
        $userAge    = (int) $profile->age;
        $userGender = strtolower($profile->gender);
        $userAgeMin = $userAge - 5;
        $userAgeMax = $userAge + 5;

        $difficultyRank = [
            'Beginner'     => 1,
            'Improving'    => 2,
            'Intermediate' => 3,
        ];

        $userRank = $difficultyRank[$safeDifficulty] ?? 1;
        $historySignals = self::buildHistorySignals($user);

        $scoredEvents = $events->map(function ($event) use (
            $profile, $userRank, $difficultyRank, $userGender, $userAgeMin, $userAgeMax, $historySignals
        ) {
            $score = 0;

            // ── 1. Content-Based: Difficulty Match (max 30 pts) ──────────────────
            $eventRank = $difficultyRank[$event->difficulty] ?? 1;
            $rankDiff  = abs($userRank - $eventRank);

            if ($rankDiff === 0) {
                $score += 30;   // Perfect fit
            } elseif ($rankDiff === 1) {
                $score += 15;   // Adjacent — still a solid recommendation
            }
            // ≥2 ranks away → 0 pts, sinks below better-matched events

            // ── 2. Content-Based: Location Match (max 20 pts) ───────────────────
            if ($profile->address && $event->location) {
                $keywords   = explode(' ', strtolower($profile->address));
                $venueLower = strtolower($event->location);
                foreach ($keywords as $word) {
                    if (strlen($word) > 4 && str_contains($venueLower, $word)) {
                        $score += 20;
                        break;
                    }
                }
            }

            // ── 3. Collaborative: Demographic Popularity (max 50 pts) ───────────
            $similarCount = EventRegistration::where('event_id', $event->id)
                ->where('status', 'registered')
                ->whereHas('user.runnerProfile', function ($q) use ($userGender, $userAgeMin, $userAgeMax) {
                    $q->where('gender', $userGender)
                      ->whereBetween('age', [$userAgeMin, $userAgeMax]);
                })->count();

            $score += min(50, $similarCount * 5);   // 5 pts per similar runner, capped at 50

            // ── 4. Running History Signals (max 20 pts) ──────────────────────────
            $score += self::calculateHistoryScore($event, $historySignals);

            // ── 5. Cold-Start Boost ──────────────────────────────────────────────
            // New events with zero registrations get a discovery nudge so they
            // aren't buried below events with collaborative signals.
            if ($event->registered_count === 0 && $score < 20) {
                $score += 15;
            }

            $event->relevance_score  = $score;
            $event->difficulty_rank  = $difficultyRank[$event->difficulty] ?? 1;

            return $event;
        });

        // Sort by relevance score descending (highest recommended first)
        return $scoredEvents->sortByDesc('relevance_score')->values();
    }

    /**
     * Build reusable runner history signals for recommendation scoring.
     */
    private static function buildHistorySignals(User $user): array
    {
        $registrations = EventRegistration::with('event')
            ->where('user_id', $user->id)
            ->where('status', EventRegistration::STATUS_REGISTERED)
            ->get();

        if ($registrations->isEmpty()) {
            return [
                'hasHistory' => false,
                'preferredDistance' => null,
                'completionRatio' => null,
                'recentPace' => null,
            ];
        }

        $distanceCounts = [];
        $completed = 0;

        foreach ($registrations as $registration) {
            $event = $registration->event;
            if (!$event) {
                continue;
            }

            $distance = self::normalizeDistanceKm($event->distance);
            if ($distance !== null) {
                $key = (string) $distance;
                $distanceCounts[$key] = ($distanceCounts[$key] ?? 0) + 1;
            }

            if ($event->status === 'completed') {
                $completed++;
            }
        }

        $preferredDistance = null;
        if (!empty($distanceCounts)) {
            arsort($distanceCounts);
            $preferredDistance = (float) array_key_first($distanceCounts);
        }

        $recentPace = $user->runnerProfile?->verified_pace;
        $completionRatio = $registrations->count() > 0 ? ($completed / $registrations->count()) : null;

        return [
            'hasHistory' => true,
            'preferredDistance' => $preferredDistance,
            'completionRatio' => $completionRatio,
            'recentPace' => $recentPace ? (float) $recentPace : null,
        ];
    }

    /**
     * Score event suitability using historical runner behavior.
     */
    private static function calculateHistoryScore(Event $event, array $historySignals): int
    {
        if (!$historySignals['hasHistory']) {
            return 0;
        }

        $historyScore = 0;
        $eventDistance = self::normalizeDistanceKm($event->distance);
        $preferredDistance = $historySignals['preferredDistance'];

        if ($eventDistance !== null && $preferredDistance !== null) {
            $distanceGap = abs($eventDistance - $preferredDistance);
            if ($distanceGap <= 2.0) {
                $historyScore += 10;
            } elseif ($distanceGap <= 5.0) {
                $historyScore += 6;
            }
        }

        $recentPace = $historySignals['recentPace'];
        if ($recentPace !== null && $eventDistance !== null) {
            if ($eventDistance <= 5 && $recentPace <= 7.5) {
                $historyScore += 5;
            } elseif ($eventDistance > 5 && $eventDistance <= 10 && $recentPace <= 7.0) {
                $historyScore += 5;
            } elseif ($eventDistance > 10 && $recentPace <= 6.5) {
                $historyScore += 5;
            }
        }

        $completionRatio = $historySignals['completionRatio'];
        if ($completionRatio !== null) {
            if ($completionRatio >= 0.75) {
                $historyScore += 5;
            } elseif ($completionRatio >= 0.5) {
                $historyScore += 3;
            }
        }

        return min(20, $historyScore);
    }

    /**
     * Convert a distance string like "21km" to float kilometers.
     */
    private static function normalizeDistanceKm($distance): ?float
    {
        if ($distance === null) {
            return null;
        }

        if (is_numeric($distance)) {
            return (float) $distance;
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', strtolower((string) $distance), $matches)) {
            return (float) $matches[1];
        }

        return null;
    }
}
