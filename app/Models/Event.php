<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'name',
        'difficulty',
        'distance',
        'description',
        'registration_start',
        'registration_end',
        'slots',
        'location',
        'event_date',
        'event_time',
        'registration_fee',
        'route_data',
        'status',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function getAvailableSlotsAttribute()
    {
        return $this->slots - $this->registrations()->where('status', 'registered')->count();
    }

    public function getFormattedDistanceAttribute()
    {
        $d = trim(strtolower((string)$this->distance));
        
        if (str_ends_with($d, 'km')) return $d;
        if (str_ends_with($d, 'm')) return $d;
        
        $val = floatval($d);
        if ($val > 0 && $val < 1) {
            return round($val * 1000) . ' m';
        }
        
        return $d . ' km';
    }
}
