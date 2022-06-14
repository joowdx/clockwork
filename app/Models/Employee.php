<?php

namespace App\Models;

use App\Traits\HasNameAccessorAndFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;

class Employee extends Model
{
    use HasNameAccessorAndFormatter;
    use Searchable;

    protected $fillable = [
        'name',
        'regular',
        'office',
        'active'
    ];

    protected $casts = [
        'name' => 'object',
    ];

    protected $appends = [
        'full_name'
    ];

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class, 'enrollments')
                ->using(Enrollment::class)
                ->withPivot(['id', 'uid'])
                ->withTimestamps()
                ->orderBy('name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, Enrollment::class, secondKey: 'enrollment_id')
                ->orderBy('time');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function getBackedUpAttribute(): ?bool
    {
        return $this->isBackedUp();
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name_format->full,
        ];
    }

    public function scopeRegular(Builder $query, bool $regular = true): void
    {
        $query->whereRegular($regular);
    }

    public function scopeActive(Builder $query, bool $active = true): void
    {
        $query->whereActive($active);
    }

    public function logsForTheDay(Carbon $date): Collection
    {
        return $this->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->values();
    }

    public function getSchedule(Carbon $date): Schedule
    {
        return $this->schedules()
            ->where('from', '<=', $date)
            ->where('to', '>=', $date)
            ->firstOrNew([], [
                'in' => Schedule::DEFAULT_IN,
                'out' => Schedule::DEFAULT_OUT,
            ]);
    }
}
