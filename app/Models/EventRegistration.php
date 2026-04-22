<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_PENDING_ALLOCATION = 'pending_allocation';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'payment_status',
        'payment_method',
        'amount_paid',
        'reference_number',
        'tshirt_size',
        'current_lat',
        'current_lng',
        'current_distance',
        'last_tracked_at',
        'live_route_data',
        'has_emergency',
        'priority_score',
        'is_off_route',
        'off_route_since',
        'off_route_count',
    ];

    protected $casts = [
        'last_tracked_at' => 'datetime',
        'off_route_since' => 'datetime',
        'is_off_route' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
