<?php

namespace App\Models;

use App\Traits\Requestable;
use Carbon\Carbon as CarbonCarbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Schedule extends Model
{
    use HasFactory, HasUlids, Requestable, SoftDeletes;

    protected $fillable = [
        'global',
        'title',
        'start',
        'end',
        'days',
        'arrangement',
        'timetable',
        'threshold',
        'office_id',
    ];

    protected $casts = [
        'start' => 'date:Y-m-d',
        'end' => 'date:Y-m-d',
        'timetable' => 'json',
        'threshold' => 'json',
    ];

    public static function search(Carbon|CarbonCarbon|string $date, ?Employee $employee = null, ?Carbon $until = null)
    {
        $date = is_string($date) ? Carbon::parse($date) : $date;

        if (isset($until)) {
            return cache()->remember(
                $date->format('Y-m-d-').$until->format('Y-m-d-').$employee?->id, 120,
                function () use ($date, $employee, $until) {
                    if (is_null($employee)) {
                        $schedules = Schedule::global()->active($date, $until)->get();

                        return (object) [
                            'weekdays' => $schedules->reject(fn ($schedule) => in_array($schedule->days, ['weekend', 'holiday'])),
                            'weekends' => $schedules->reject(fn ($schedule) => $schedule->days === 'weekday'),
                        ];
                    }

                    $schedules = $employee->schedules()
                        ->active($date, $until)
                        ->completelyApproved()
                        ->get();

                    return $schedules->isNotEmpty()
                    ? (object) [
                        'weekdays' => $schedules->reject(fn ($schedule) => in_array($schedule->days, ['weekend', 'holiday'])),
                        'weekends' => $schedules->reject(fn ($schedule) => $schedule->days === 'weekday'),
                    ] : static::search($date, until: $until);
                }
            );
        }

        return cache()->remember(
            $date->format('Y-m-d-').$employee?->id, 120,
            function () use ($date, $employee) {
                $holiday = Holiday::search($date, false);

                if (is_null($employee)) {
                    return Schedule::global()->active($date)->where('days', $holiday || $date->isWeekend() ? 'weekend' : 'weekday')->first()
                        ?? Schedule::global()->active($date)->where('days', 'everyday')->first();
                }

                return $employee->schedules()->active($date)->where('days', $holiday || $date->isWeekend() ? 'weekend' : 'weekday')->first()
                    ?? $employee->schedules()->active($date)->where('days', 'everyday')->first()
                    ?? static::search($date);
            }
        );
    }

    public function period(): Attribute
    {
        return Attribute::make(
            function (): string {
                if ($this->start && $this->end) {
                    return $this->start->format('Y-M-d').' – '.$this->end->format('Y-M-d');
                }

                if ($this->start) {
                    return ($this->start->isBefore(now()) ? 'Since: ' : 'Starting: ').$this->start->format('Y-M-d');
                }

                if ($this->end) {
                    return 'Until: '.$this->end->format('Y-M-d');
                }

                return 'No period';
            }
        )->shouldCache();
    }

    public function time(): Attribute
    {
        return Attribute::make(
            function () {
                if ($this->arrangement === 'standard-work-hour') {
                    return $this->timetable['break'] > 0
                        ? $this->timetable['p1'].'–'.$this->timetable['p2'].' & '.$this->timetable['p3'].'–'.$this->timetable['p4']
                        : $this->timetable['p1'].'–'.$this->timetable['p4'];
                }
            }
        )->shouldCache();
    }

    public function everyday(): Attribute
    {
        return Attribute::make(
            fn () => $this->days === 'everyday'
        )->shouldCache();
    }

    public function holiday(): Attribute
    {
        return Attribute::make(
            fn () => $this->days === 'holiday'
        )->shouldCache();
    }

    public function weekday(): Attribute
    {
        return Attribute::make(
            fn () => $this->days === 'weekday'
        )->shouldCache();
    }

    public function weekend(): Attribute
    {
        return Attribute::make(
            fn () => $this->days === 'weekend'
        )->shouldCache();
    }

    public function current(): Attribute
    {
        return Attribute::make(
            function (): bool {
                if ($this->start && $this->end) {
                    return now()->between($this->start, $this->end);
                }

                if ($this->start) {
                    return now()->gte($this->start);
                }

                if ($this->end) {
                    return now()->lte($this->end);
                }

                return false;
            }
        )->shouldCache();
    }

    public function isActive(?Carbon $day = null): bool
    {
        $day = $day ?? today();

        return $this->start->lte($day) && $this->end->gte($day);
    }

    public function scopeGlobal(Builder $query, bool $global = true): void
    {
        $query->where('global', $global);
    }

    public function scopeActive(Builder $query, Carbon|string|null $date = null, Carbon|string|null $until = null): void
    {
        $date = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date))->startOfDay() : today();

        $until = $until ? ($until instanceof Carbon ? $until : Carbon::parse($until))->endOfDay() : today()->endOfDay();

        $query->where(function (Builder $query) use ($date, $until) {
            $query->orWhere(fn ($q) => $q->where('schedules.start', '<=', $date->format('Y-m-d'))->where('schedules.end', '>=', $date->format('Y-m-d')));

            $query->orWhere(fn ($q) => $q->where('schedules.start', '<=', $until->format('Y-m-d'))->where('schedules.end', '>=', $until->format('Y-m-d')));

            // $query->orWhere(fn ($q) => $q->where('schedules.start', '>=', $date->format('Y-m-d'))->where('schedules.end', '>=', $until->format('Y-m-d')));

            // $query->orWhere(fn ($q) => $q->where('schedules.start', '<=', $date->format('Y-m-d'))->where('schedules.end', '<=', $until->format('Y-m-d')));
        });
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'shift')
            ->using(Shift::class)
            ->withPivot('timetable');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
