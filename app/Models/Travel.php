<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Travel extends Model
{
    use HasFactory, HasUuids;

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
