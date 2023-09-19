<?php

namespace App\Models;

use App\Enums\TravelCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Travel extends Model
{
    use HasUuids;

    protected $casts = [
        'category' => TravelCategory::class,
        'dates' => 'array',
        'data' => 'object',
    ];

    protected $fillable = [
        'control_number',
        'dates',
        'official',
        'category',
        'data',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'trips')
            ->using(Trip::class);
    }
}
