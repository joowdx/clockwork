<?php

namespace App\Models;

use App\Helpers\NumberRangeCompressor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Timesheet extends Model
{
    use HasFactory, HasUlids, Prunable;

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

    public function certifyPeriod(string $period, ?User $user = null, string $level = null): void
    {
        $period = match($period) {
            '1st' => 'first',
            '2nd' => 'second',
            'full' => 'full',
            default => $period,
        };

        if (! in_array($period, ['first', 'second', 'full'])) {
            throw new InvalidArgumentException("Argument \$period invalid.");
        }

        if (! in_array($level, ['supervisor', 'head'])) {
            throw new InvalidArgumentException("Argument \$level invalid.");
        }

        if ($level && ! $user) {
            throw new InvalidArgumentException("Argument \$user required for \$level.");
        }

        match($level) {
            'supervisor', 'head' => $this->forceFill([
                "certification->{$period}->{$level}->at" => now(),
                "certification->{$period}->{$level}->name" => $user?->employee?->titled_name,
                "certification->{$period}->{$level}->signature" => $this->employee->signature?->signatureBase64,
                "certification->{$period}->{$level}->certificate" => $user?->signature?->certificateBase64,
                "certification->{$period}->{$level}->password" => $user?->signature?->password ? encrypt($user?->signature?->password) : null,
            ])->save(),

            default => $this->forceFill([
                "certification->{$period}->at" => now(),
                "certification->{$period}->by" => $this->employee->titled_name,
                "certification->{$period}->signature" => $this->employee->signature?->signatureBase64,
                "certification->{$period}->certificate" => $this->employee->signature?->certificateBase64,
                "certification->{$period}->password" => $this->employee->signature?->password ? encrypt($this->employee->signature?->password) : null,
            ])->save(),
        };
    }

    public function certificationDetails(string $period = 'full', string|false|null $level = false): mixed
    {
        $period = match($period) {
            '1st' => 'first',
            '2nd' => 'second',
            'full' => 'full',
            default => $period,
        };

        if (! in_array($period, ['first', 'second', 'full'])) {
            throw new InvalidArgumentException('Argument $period invalid.');
        }

        $details = @$this->certification->{$period};

        if ($level) {

            if (! in_array($level, ['supervisor', 'head'])) {
                throw new InvalidArgumentException('Argument $level invalid.');
            }

            return (object) [
                'at' => @$details?->{$level}?->at ? Carbon::parse(@$details->{$level}->at) : null,
                'by' => @$details?->{$level}?->name,
                'certificate' => @$details?->{$level}?->certificate,
                'password' => @$details?->{$level}?->key,
            ];
        }

        return (object) [
            'at' => Carbon::parse(@$details->at),
            'by' => @$details->by,
            'supervisor' => [
                'at' => Carbon::parse(@$details->supervisor?->at),
                'name' => @$details->supervisor?->name,
                'certificate' => @$details->supervisor?->certificate,
                'password' => @$details->supervisor?->key,
            ],
            'head' => [
                'at' => Carbon::parse(@$details->head?->at),
                'name' => @$details->head?->name,
                'certificate' => @$details->head?->certificate,
                'password' => @$details->head?->key,
            ],
        ];
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

    public function certified(): Attribute
    {
        return Attribute::make(
            function () {
                return match(true) {
                    $this->certified_first && $this->certified_second => '1st,2nd',
                    $this->certified_full => 'full',
                    $this->certified_first => '1st',
                    $this->certified_second => '2nd',
                    default => null,
                };
            }
        );
    }

    public function certifiedFirst(): Attribute
    {
        return Attribute::make(
            fn () => @$this->certification?->first?->at && @$this->certification?->first?->by
        );
    }

    public function certifiedSecond(): Attribute
    {
        return Attribute::make(
            fn () => @$this->certification?->second?->at && @$this->certification?->second?->by
        );
    }

    public function certifiedFull(): Attribute
    {
        return Attribute::make(
            fn () => $this->certified_first && $this->certified_second || @$this->certification?->full?->at && @$this->certification?->full?->by
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

                    return "$days days; ".($undertime ? "$undertime UT" : '');
                };

                return match ($this->span) {
                    'overtime' => $overtime(),
                    'full', '1st', '2nd', 'regular' => $standard(),
                    default => null,
                };
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
        $period = match($period) {
            '1st' => 'first',
            '2nd' => 'second',
            'full' => 'full',
            default => $period,
        };

        if ($period && ! in_array($period, ['first', 'second', 'full'])) {
            throw new InvalidArgumentException('Argument $period invalid.');
        }

        if ($level && ! in_array($level, ['supervisor', 'head'])) {
            throw new InvalidArgumentException('Argument $level invalid.');
        }

        $certified = function (&$query, $period) use ($level) {
            if ($level === null) {
                return $query->orWhereNot("certification->{$period}->at", null)->orWhereNot("certification->{$period}->at", '');
            }

            return $query->orWhereNot("certification->{$period}->{$level}->at", null)->orWhereNot("certification->{$period}->{$level}->at", '');
        };

        $filter = function (&$query, $period) use ($certified) {
            if ($period === null) {
                $certified($query, 'first');

                $certified($query, 'second');

                $certified($query, 'full');

                return $query;
            }

            if ($period === 'full') {
                $query->orWhere(function ($query) use ($certified) {
                    $certified($query, 'full');
                });

                $query->orWhere(function ($query) use ($certified) {
                    $certified($query, 'first');

                    $certified($query, 'second');
                });

                return $query;
            }

            return $certified($query, $period);
        };

        return $builder->where(fn ($query) => $filter($query, $period));
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

    public function export(): MorphOne|MorphMany
    {
        return $this->morphOne(Export::class, 'exportable');
    }
}
