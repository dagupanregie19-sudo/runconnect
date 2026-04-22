<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeLog extends Model
{
    protected $fillable = [
        'user_challenge_id',
        'distance_km',
        'logged_date',
        'notes',
        'duration_seconds',
        'start_coords',
        'end_coords',
        'route_points',
    ];

    protected $casts = [
        'logged_date' => 'date',
        'distance_km' => 'decimal:2',
        'duration_seconds' => 'integer',
        'start_coords' => 'array',
        'end_coords' => 'array',
        'route_points' => 'array',
    ];

    public function challenge()
    {
        return $this->belongsTo(UserChallenge::class, 'user_challenge_id');
    }
}
