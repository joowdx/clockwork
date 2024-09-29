<?php

namespace App\Models;

use App\Helpers\NumberRangeCompressor;
use App\Traits\TimelogsHasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Timesheet extends Model
{
    use HasFactory, HasUlids, Prunable;
    use TimelogsHasher;

    protected $fillable = [
        'month',
        'details',
        'digest',
    ];

    protected $casts = [
        'details' => 'json',
        'certification' => 'object',
    ];

    protected string $span = 'full';

    protected array $dates = [];

    protected ?int $from = null;

    protected ?int $to = null;

    protected static function booted()
    {
        static::deleting(fn (self $timesheet) => $timesheet->export?->delete());
    }

    public function setSpan(string $span): self
    {
        $this->span = $span;

        return $this;
    }

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

    public function setCustomDates(array $dates): self
    {
        $this->span = 'dates';

        $this->dates = collect($dates)
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->toArray();

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

        $this->span = 'range';

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
            'dates' => 'customDates',
            'range' => 'customRange',
            default => 'fullMonth',
        };
    }

    public function days(): Attribute
    {
        return Attribute::make(
            fn () => $this->{$this->getPeriod()}->filter->present->reject->half->count()
                + $this->{$this->getPeriod()}->filter->present->filter->half->count() / 2
        );
    }

    public function absences(): Attribute
    {
        return Attribute::make(
            fn () => $this->{$this->getPeriod()}->filter->absent->count()
        );
    }

    public function invalids(): Attribute
    {
        return Attribute::make(
            fn () => $this->{$this->getPeriod()}->filter->invalid->count()
        );
    }

    public function missed(): Attribute
    {
        return Attribute::make(
            fn () => $this->{$this->getPeriod()}->map->punch->filter()->map(function ($timetable) {
                return @collect($timetable)->filter->missed->count();
            })->sum()
        );
    }

    public function period(): Attribute
    {
        return Attribute::make(
            function () {
                $month = Carbon::parse($this->month);

                $formatted = (function () {
                    $days = $this->{$this->getPeriod()}->map->date->map(fn ($date) => $date->format('j'))->sort()->values()->toArray();

                    return (new NumberRangeCompressor)($days).' '.$this->{$this->getPeriod()}->map->date->first()?->format('F Y');
                });

                return match ($this->span) {
                    '1st' => '01-15 '.$month->format('F Y'),
                    '2nd' => '16-'.$month->endOfMonth()->day.' '.$month->format('F Y'),
                    'overtime' => $formatted(),
                    'dates' => $formatted(),
                    'range' => $this->from.'-'.$this->to.' '.$month->format('F Y'),
                    default => '01-'.$month->endOfMonth()->day.' '.$month->format('F Y'),
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
                $format = function ($minutes) {
                    $hours = (int) ($minutes / 60);

                    $mins = $minutes % 60;

                    return ($hours > 0 ? "{$hours}hrs" : '').($mins > 0 ? " {$mins}mins" : '');
                };

                $overtime = function () use ($format) {
                    $wd = $this->overtimeWork->filter->regular->sum('overtime');

                    $we = $this->overtimeWork->reject->regular->sum('overtime');

                    return ($wd > 0 ? "wkdy:{$format($wd)}" : '').($wd > 0 ? ', ' : '').($we > 0 ? "wknd:{$format($we)}" : '');
                };

                $standard = function () use ($format) {
                    $days = $this->{$this->getPeriod()}->filter->present->reject->half->count()
                        + $this->{$this->getPeriod()}->filter->present->filter->half->count() / 2;

                    $undertime = $format($this->{$this->getPeriod()}->filter->present->sum('undertime'));

                    return "{$days}days; ".($undertime ? "$undertime UT" : '');
                };

                return match ($this->span) {
                    'overtime' => $overtime(),
                    'full', '1st', '2nd', 'regular' => $standard(),
                    default => null,
                };
            }
        );
    }

    public function certified(): Attribute
    {
        return Attribute::make(
            function () {
                return $this->exports->reduce(function ($carry, $export) {
                    $carry['1st'] = $carry['1st'] || $export->details->period === '1st';
                    $carry['2nd'] = $carry['2nd'] || $export->details->period === '2nd';
                    $carry['full'] = $carry['full'] || $export->details->period === 'full';

                    return $carry;
                }, ['1st' => false, '2nd' => false, 'full' => false]);
            }
        );
    }

    public function verified(): Attribute
    {
        return Attribute::make(
            function () {
                return $this->exports->reduce(function ($carry, $export) {
                    $carry['1st'] = $carry['1st'] || $export->details->period === '1st' && @$export->details->verification->head->at;
                    $carry['2nd'] = $carry['2nd'] || $export->details->period === '2nd' && @$export->details->verification->head->at;
                    $carry['full'] = $carry['full'] || $export->details->period === 'full' && @$export->details->verification->head->at;

                    return $carry;
                }, ['1st' => false, '2nd' => false, 'full' => false]);
            }
        );
    }

    public function scopeMonth(Builder $query, Carbon|string|null $month = null): void
    {
        $query->whereDate('month', ($month !== null ? ($month instanceof Carbon ? $month : Carbon::parse($month)) : today())->startOfMonth());
    }

    public function scopeRange(Builder $query, Carbon|string|null $from = null, Carbon|string|null $to = null): void
    {
        $from = ($from ? ($from instanceof Carbon ? $from : Carbon::parse($from)) : today())->startOfMonth();

        $to = ($to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : today())->endOfMonth();

        $query->whereBetween('month', [$from, $to]);
    }

    public function scopeCertified(Builder $builder, ?string $period = null, ?string $level = null): Builder
    {
        $period = match ($period) {
            'first' => '1st',
            'second' => '2nd',
            'full' => 'full',
            default => $period,
        };

        if ($period && ! in_array($period, ['1st', '2nd', 'full'])) {
            throw new InvalidArgumentException('Argument $period invalid.');
        }

        if ($level && ! in_array($level, ['supervisor', 'head'])) {
            throw new InvalidArgumentException('Argument $level invalid.');
        }

        return $builder->whereHas('exports', function ($query) use ($period, $level) {
            if ($period) {
                $query->where('details->period', $period);
            }

            if ($level) {
                $query->where("details->$level", true);
            }
        });
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subYears(2));
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

    public function customDates(): HasMany
    {
        $month = Carbon::parse($this->month);

        $relationship = $this->hasMany(Timetable::class)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date');

        $relationship->where(function ($query) {
            foreach ($this->dates as $date) {
                $query->orWhereDate('date', $date);
            }
        });

        return $relationship;
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

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(Timelog::class, Enrollment::class, 'timesheets.id', 'uid', secondLocalKey: 'uid')
            ->join('scanners', fn ($join) => $join->on('scanners.uid', 'timelogs.device')->on('scanners.id', 'enrollment.scanner_id'))
            ->join('timesheets', fn ($join) => $join->on('timesheets.employee_id', 'enrollment.employee_id'))
            ->whereColumn(DB::raw('extract(month from timelogs.time)'), DB::raw('extract(month from timesheets.month)'))
            ->whereColumn(DB::raw('extract(year from timelogs.time)'), DB::raw('extract(year from timesheets.month)'))
            ->whereColumn('timelogs.uid', 'enrollment.uid')
            ->whereColumn('timesheets.employee_id', 'enrollment.employee_id')
            ->where('enrollment.active', true)
            ->latest('time')
            ->latest('timelogs.id');
    }

    public function exports(): MorphMany
    {
        return $this->morphMany(Export::class, 'exportable');
    }

    public function firstHalfExportable(): MorphOne
    {
        return $this->exports()
            ->one()
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('details->period', '1st');
            });
    }

    public function secondHalfExportable(): MorphOne
    {
        return $this->exports()
            ->one()
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('details->period', '2nd');
            });
    }

    public function fullMonthExportable(): MorphOne
    {
        return $this->exports()
            ->one()
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('details->period', 'full');
            });
    }
}
