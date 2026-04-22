<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'level',
        'status',
        'target_distance',
        'distance_logged',
        'daily_target',
        'duration_days',
        'started_at',
        'deadline_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'completed_at' => 'datetime',
        'target_distance' => 'decimal:2',
        'distance_logged' => 'decimal:2',
        'daily_target' => 'decimal:2',
    ];

    /**
     * Challenge configuration for each level.
     */
    public const LEVELS = [
        'beginner' => [
            'label' => 'Beginner',
            'icon' => 'fa-solid fa-seedling',
            'color' => '#22c55e',
            'duration_days' => 7,
            'target_distance' => 5,
            'daily_target' => null,
            'description' => 'Finish 5km in 1 week',
            'on_success' => 'improving',
            'on_failure' => 'beginner', // stays
        ],
        'improving' => [
            'label' => 'Improving',
            'icon' => 'fa-solid fa-arrow-trend-up',
            'color' => '#3b82f6',
            'duration_days' => 14,
            'target_distance' => 10,
            'daily_target' => null,
            'description' => 'Finish 10km in 2 weeks',
            'on_success' => 'intermediate',
            'on_failure' => 'beginner',
        ],
        'intermediate' => [
            'label' => 'Intermediate',
            'icon' => 'fa-solid fa-bolt',
            'color' => '#f59e0b',
            'duration_days' => 21,
            'target_distance' => 15,
            'daily_target' => null,
            'description' => 'Finish 15km in 3 weeks',
            'on_success' => 'intermediate', // stays at top
            'on_failure' => 'improving',
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(ChallengeLog::class);
    }

    /**
     * Get the config for this challenge's level.
     */
    public function getLevelConfigAttribute()
    {
        return self::LEVELS[$this->level] ?? null;
    }

    /**
     * Get progress percentage (0-100).
     */
    public function getProgressAttribute()
    {
        if ($this->target_distance <= 0)
            return 0;
        return min(100, round(($this->distance_logged / $this->target_distance) * 100));
    }

    /**
     * Get remaining days.
     */
    public function getRemainingDaysAttribute()
    {
        if (!$this->deadline_at)
            return 0;
        $diff = now()->diffInDays($this->deadline_at, false);
        return max(0, (int) ceil($diff));
    }

    /**
     * Check if deadline has passed.
     */
    public function getIsExpiredAttribute()
    {
        return $this->deadline_at && now()->greaterThan($this->deadline_at);
    }

    /**
     * Check if today's daily target is met.
     */
    public function getTodayLoggedAttribute()
    {
        return $this->logs()->where('logged_date', today()->toDateString())->sum('distance_km');
    }

    /**
     * Count how many days the daily target was met.
     */
    public function getDaysCompletedAttribute()
    {
        if (!$this->daily_target)
            return 0;

        return $this->logs()
            ->selectRaw('logged_date, SUM(distance_km) as total')
            ->groupBy('logged_date')
            ->havingRaw('SUM(distance_km) >= ?', [$this->daily_target])
            ->get()
            ->count();
    }
}
