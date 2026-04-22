<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RunnerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'fitness_level',
        'verified_pace',
        'first_name',
        'last_name',
        'middle_name',
        'name_extension',
        'age',
        'gender',
        'address',
        'phone_number',
        'health_conditions',
    ];

    protected $casts = [
        'health_conditions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
