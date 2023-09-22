<?php

namespace App\Models;

use App\Traits\HasNameAccessorAndFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;

class Employee extends Model
{
    use HasFactory;
    use HasNameAccessorAndFormatter;
    use HasUlids;
    use Searchable;

    protected $fillable = [
        'name',
        'regular',
        'office',
        'active',
        'csc_format',
        'groups',
    ];

    protected $casts = [
        'name' => 'object',
        'groups' => 'array',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $hidden = [
        'csc_format',
    ];

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class, 'enrollments')
            ->using(Enrollment::class)
            ->withPivot(['id', 'uid', 'enabled'])
            ->withTimestamps()
            ->orderBy('name');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(Timelog::class, Enrollment::class, 'employees.id', 'uid', secondLocalKey: 'uid')
            ->join($this->getTable(), function ($join) {
                $join->on('employees.id', 'enrollments.employee_id');
                $join->on('enrollments.scanner_id', 'timelogs.scanner_id');
            })
            ->where('enrollments.enabled', true)
            ->latest('time')
            ->latest('timelogs.id');
    }

    public function toSearchableArray(): array
    {
        return [
            'name->first' => $this->name?->first,
            'name->middle' => $this->name?->middle,
            'name->last' => $this->name?->last,
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
        return $this->logsForTheDay($date)->isEmpty() && $date->clone()->startOfDay()->lte(today());
    }

    public function ellipsize(int $length = 30, string $format = 'fullStartLastInitialMiddle', string $ellipsis = '…')
    {
        return strlen($this->name_format->$format) > $length ? substr($this->name_format->$format, 0, $length).$ellipsis : $this->name_format->$format;
    }
}
