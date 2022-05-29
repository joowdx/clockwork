<?php

namespace App\Models;

use App\Models\SQLite\Employee as BackupEmployee;
use App\Models\SQLite\TimeLog as BackupTimeLog;
use App\Traits\HasNameAccessorAndFormatter;
use App\Traits\HasUniversallyUniqueIdentifier;
use Awobaz\Compoships\Compoships;
use Awobaz\Compoships\Database\Eloquent\Relations\BelongsTo;
use Awobaz\Compoships\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

class Employee extends Model
{
    use Compoships;
    use HasNameAccessorAndFormatter;
    use Searchable;

    protected $fillable = [
        'biometrics_id',
        'name',
        'regular',
        'office',
        'user_id',
    ];

    protected $casts = [
        'name' => 'object',
    ];

    protected $appends = [
        'full_name'
    ];

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class)
                ->using(EmployeeScanner::class)
                ->withPivot('uid')
                ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function getLogsAttribute(): ?EloquentCollection
    {
        return $this->relationLoaded('backupLogs') ? $this->backupLogs : ($this->relationLoaded('mainLogs') ? $this->mainLogs : null);
    }

    public function mainLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class, ['scanner_id', 'user_id'], ['scanner_id', 'user_id']);
    }

    public function backupLogs(): HasMany
    {
        return $this->hasMany(BackupTimeLog::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function getBackedUpAttribute(): ?bool
    {
        return $this->isBackedUp();
    }

    public function isBackedUp(): ?bool
    {
        return $this->backup?->active;
    }

    public function backup(): HasOne
    {
        return $this->hasOne(BackupEmployee::class);
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
        return $this->logs->filter(fn ($t) => $t->time->isSameDay($date))->values();
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
