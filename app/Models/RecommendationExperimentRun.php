<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One saved evaluation experiment (weights, split, K, aggregate metrics).
 *
 * Table: recommendation_experiment_runs
 * Written by: php artisan reco:evaluate | reco:grid-search
 * Use for thesis tables: query latest runs or export JSON metrics column.
 */
class RecommendationExperimentRun extends Model
{
    protected $fillable = [
        'name',
        'k',
        'split_strategy',
        'test_ratio',
        'weights',
        'train_size',
        'test_size',
        'metrics',
        'notes',
    ];

    protected $casts = [
        'weights' => 'array',
        'metrics' => 'array',
        'test_ratio' => 'float',
    ];
}

