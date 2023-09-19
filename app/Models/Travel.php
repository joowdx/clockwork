<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Travel extends Model
{
    use HasUuids;

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
