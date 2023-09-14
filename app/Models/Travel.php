<?php

namespace App\Models;

class Travel extends Model
{
    protected $casts = [
        'type' => TravelType::class,
        'dates' => 'array',
        'data' => 'object',
    ];

    protected $fillable = [
        'control_number',
        'dates',
        'official',
        'type',
        'data',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'trips')
            ->using(Trip::class);
    }
}
