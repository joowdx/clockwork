<?php

namespace App\Models;

use App\Traits\HasNameAccessorAndFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, Enrollment::class, 'employees.id', 'uid', secondLocalKey: 'uid')
            ->join($this->getTable(), function ($join) {
                $join->on('employees.id', 'enrollments.employee_id');
                $join->on('enrollments.scanner_id', 'time_logs.scanner_id');
            })
            ->select('time_logs.*')
            ->latest('time')
            ->latest('time_logs.id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function toSearchableArray(): array
    {
        return [
            $this->getKeyName() => $this->getKey(),
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

    public function scopeUnenrolled(Builder $query): void
    {
        $query->whereDoesntHave('scanners');
    }

    public function logsForTheDay(Carbon $date): Collection
    {
        return $this->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->values();
    }

    public function absentForTheDay(Carbon $date): bool
    {
        return $this->logsForTheDay($date)->isEmpty() && $date->lte(today());
    }

    public function ellipsize(int $length = 30, string $format = 'fullStartLastInitialMiddle', string $ellipsis = '…')
    {
        return strlen($this->name_format->$format) > $length ? substr($this->name_format->$format, 0, $length) . $ellipsis : $this->name_format->$format;
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
