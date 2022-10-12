<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class Schedule extends Pivot
{
    use HasUniversallyUniqueIdentifier;

    const DEFAULT_DAYS = [
        1, 2, 3, 4, 5,
    ];

    protected $table = 'schedules';

    protected $fillable = [
        'from',
        'to',
        'days',
        'shift_id',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeActive(Builder $query, ?Carbon $date = null): void
    {
        $date ??= today();

        $query->whereDate('from', '<=', $date);

        $query->whereDate('to', '>', $date->clone()->endOfDay());
    }
}
