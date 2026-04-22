<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of imported survey data used only for offline recommendation evaluation.
 *
 * Table: recommendation_training_samples
 * Populated by: php artisan reco:import-excel
 * Consumed by: RecommendationEvaluationService
 */
class RecommendationTrainingSample extends Model
{
    protected $fillable = [
        'source_row',
        'observed_at',
        'full_name',
        'age_group',
        'gender',
        'running_experience',
        'race_category',
        'total_events_joined',
        'running_club_member',
        'average_pace_category',
        'exact_avg_pace_min_per_km',
        'best_5km_time',
        'health_conditions',
        'research_consent',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'running_club_member' => 'boolean',
        'research_consent' => 'boolean',
        'exact_avg_pace_min_per_km' => 'float',
    ];
}

