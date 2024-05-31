<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class Timesheet extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'month',
        'details',
    ];

    protected $casts = [
        'details' => 'json',
    ];

    protected string $span = 'full';

    protected ?int $from = null;

    protected ?int $to = null;

    public function setFullMonth(): self
    {
        $this->span = 'full';

        return $this;
    }

    public function setRegularDays(): self
    {
        $this->span = 'regular';

        return $this;
    }

    public function setOvertimeWork(): self
    {
        $this->span = 'overtime';

        return $this;
    }

    public function setFirstHalf(): self
    {
        $this->span = '1st';

        return $this;
    }

    public function setSecondHalf(): self
    {
        $this->span = '2nd';

        return $this;
    }

    public function setCustomRange(int $from, int $to): self
    {
        if ($from > $to || $from < 0) {
            throw new InvalidArgumentException('Argument $from cannot be greater than $to or less than zero');
        }

        if ($to > $max = Carbon::parse($this->month)->endOfMonth()->day) {
            throw new InvalidArgumentException('Argument $to cannot be greater than the value '.$max);
        }

        $this->span = 'custom';

        $this->from = $from;

        $this->to = $to;

        return $this;
    }

    public function getPeriod(): string
    {
        return match ($this->span) {
            '1st' => 'firstHalf',
            '2nd' => 'secondHalf',
            'overtime' => 'overtimeWork',
            'regular' => 'regularDays',
            'custom' => 'customRange',
            default => 'fullMonth',
        };
    }

    public function period(): Attribute
    {
        return Attribute::make(
            function () {
                $month = Carbon::parse($this->month);

                return match ($this->span) {
                    '1st' => $month->format('F').' 01-15',
                    '2nd' => $month->format('F').' 16-'.$month->endOfMonth()->day,
                    'overtime' => $month->format('F ').$this->overtimeWork->map->date->map->format('d')->join(','),
                    'custom' => $month->format('F').' '.$this->from.'-'.$this->to,
                    default => $month->format('F').' 01-'.$month->endOfMonth()->day,
                };
            },
        )->shouldCache();
    }

    public function from(): Attribute
    {
        return Attribute::make(
            fn () => $this->span === '2nd' ? 16 : 1
        );
    }

    public function to(): Attribute
    {
        return Attribute::make(
            fn () => $this->span === '1st' ? 15 : Carbon::parse($this->month)->endOfMonth()->day
        );
    }

    public function range(): Attribute
    {
        return Attribute::make(
            function () {
                $month = Carbon::parse($this->month);

                return match ($this->span) {
                    '1st' => range(1, 15),
                    '2nd' => range(16, $month->endOfMonth()->day),
                    default => range(1, $month->endOfMonth()->day),
                };
            }
        )->shouldCache();
    }

    public function month(): Attribute
    {
        return Attribute::make(
            get: fn ($month): string => Carbon::parse($month)->format('Y-m'),
            set: fn ($month): Carbon => Carbon::parse($month)->startOfMonth(),
        );
    }

    public function total(): Attribute
    {
        return Attribute::make(
            function () {
                $overtime = function () {
                    $overtime = $this->overtimeWork->sum('overtime');

                    $hours = (int) ($overtime / 60);

                    $mins = $overtime % 60;

                    return "$hours hrs".($mins > 0 ? " $mins mins" : '');
                };

                return match ($this->span) {
                    'overtime' => $overtime(),
                    default => null,
                };
            }
        );
    }

    public function scopeMonth(Builder $query, Carbon|string|null $month = null)
    {
        $query->whereDate('month', ($month !== null ? ($month instanceof Carbon ? $month : Carbon::parse($month)) : today())->startOfMonth());
    }

    public function scopeRange(Builder $query, Carbon|string|null $from = null, Carbon|string|null $to = null)
    {
        $from = ($from ? ($from instanceof Carbon ? $from : Carbon::parse($from)) : today())->startOfMonth();

        $to = ($to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : today())->endOfMonth();

        $query->whereBetween('month', [$from, $to]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fullMonth(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->orderBy('date');
    }

    public function regularDays(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->regularDays()
            ->orderBy('date');
    }

    public function overtimeWork(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->overtimeWork()
            ->orderBy('date');
    }

    public function firstHalf(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->firstHalf()
            ->orderBy('date');
    }

    public function secondHalf(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->secondHalf()
            ->orderBy('date');
    }

    public function customRange(): HasMany
    {
        return $this->hasMany(Timetable::class)
            ->whereDay('date', '>=', $this->from ?? 1)
            ->whereDay('date', '<=', $this->to ?? Carbon::parse($this->month)->endOfMonth()->day)
            ->orderBy('date');
    }

    public function timetables(): HasMany
    {
        return match ($this->span) {
            '1st' => $this->firstHalf(),
            '2nd' => $this->secondHalf(),
            'overtime' => $this->overtimeWork(),
            'customRange' => $this->customRange(),
            default => $this->fullMonth(),
        };
    }
}
