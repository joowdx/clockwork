<?php

namespace App\Models;

use App\Enums\AttachmentClassification;
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
        'span',
        'timesheet_id',
        'employee_id',
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
        static::creating(fn (self $timesheet) => $timesheet->timesheet_id ??= $timesheet->id);

        static::deleting(function (self $timesheet) {
            $timesheet->timesheets()->lazyById()->each->delete();

            $timesheet->attachments()->lazyById()->each->delete();
        });
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
        return match ($this->getAttribute('span') ?? $this->span) {
            '1st' => 'firstHalf',
            '2nd' => 'secondHalf',
            'overtime' => 'overtimeWork',
            'regular' => 'regularDays',
            'dates' => 'customDates',
            'range' => 'customRange',
            default => 'fullMonth',
        };
    }

    public function getUndertime(bool $string = false)
    {
        $timetables = $this->{$this->relationLoaded('records') ? 'records' : $this->getPeriod()}->filter->present;

        $undertime = [
            'days' => $timetables->filter->undertime->count(),
            'minutes' => $timetables->sum('undertime'),
        ];

        if (! $string) {
            return $undertime;
        }

        if ($undertime['minutes'] === 0) {
            return 'No undertime';
        }

        $days = $undertime['days'];

        $minutes = $undertime['minutes'];

        $hours = (int) ($minutes / 60);

        $mins = $minutes % 60;

        return trim(($days > 0 ? "({$days} days) " : '').($hours > 0 ? "{$hours} hrs" : '').($mins > 0 ? " {$mins} mins" : ''));
    }

    public function getOvertime(bool $string = false)
    {
        $timetables = $this->{$this->relationLoaded('records') ? 'records' : $this->getPeriod()}->filter->present;

        $overtime = [
            'weekdays' => $timetables->filter->regular->sum('overtime'),
            'weekends' => $timetables->reject->regular->sum('overtime'),
        ];

        if (! $string) {
            return $overtime;
        }

        if ($overtime['weekdays'] === 0 && $overtime['weekends'] === 0) {
            return 'No overtime';
        }

        $format = function ($minutes) {
            $hours = (int) ($minutes / 60);

            $mins = $minutes % 60;

            return ($hours > 0 ? "{$hours} hrs" : '').($mins > 0 ? " {$mins} mins" : '');
        };

        return ($overtime['weekdays'] > 0 ? "wkd: {$format($overtime['weekdays'])}" : '').
            ($overtime['weekdays'] > 0 && $overtime['weekends'] > 0 ? ' | ' : '').
            ($overtime['weekends'] > 0 ? "wke: {$format($overtime['weekends'])}" : '');
    }

    public function getMissed(bool $string)
    {
        $timetables = $this->{$this->relationLoaded('records') ? 'records' : $this->getPeriod()}->filter->present;

        $days = $timetables->map->punch
            ->filter()
            ->map(fn ($punch) => @collect($punch)->filter->missed->count())
            ->filter();

        $missed = [
            'days' => $days->count(),
            'count' => $days->sum(),
        ];

        if (! $string) {
            return $missed;
        }

        if ($missed['count'] === 0) {
            return 'Nothing missed';
        }

        return "({$missed['days']} days) {$missed['count']} missed";
    }

    public function leaderSigner(): Attribute
    {
        return Attribute::make(function () {
            if ($this->relationLoaded('signers')) {
                return $this->signers->first(fn ($signer) => $signer->meta === 'leader');
            }

            return $this->signers()->where('meta', 'leader')->first();
        });
    }

    public function directorSigner(): Attribute
    {
        return Attribute::make(function () {
            if ($this->relationLoaded('signers')) {
                return $this->signers->first(fn ($signer) => $signer->meta === 'director');
            }

            return $this->signers()->where('meta', 'director')->first();
        });
    }

    public function days(): Attribute
    {
        return Attribute::make(
            fn () => $this->relationLoaded('records')
                ? $this->records->filter->present->reject->half->count()
                    + $this->records->filter->present->filter->half->count() / 2
                : $this->{$this->getPeriod()}->filter->present->reject->half->count()
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

    public function undertime(): Attribute
    {
        return Attribute::make(
            fn () => $this->relationLoaded('records') ? $this->records->sum('undertime') : $this->{$this->getPeriod()}->sum('undertime')
        );
    }

    public function overtime(): Attribute
    {
        return Attribute::make(
            fn () => $this->relationLoaded('records') ? $this->records->sum('overtime') : $this->{$this->getPeriod()}->sum('overtime')
        );
    }

    public function period(): Attribute
    {
        return Attribute::make(
            function () {
                $month = Carbon::parse($this->month);

                $formatted = (function () {
                    $days = $this->{$this->getPeriod()}->map->date->map(fn ($date) => $date->format('j'))->sort()->values()->toArray();

                    return (new NumberRangeCompressor)($days).' '.$this->{$this->getPeriod()}->map->date->first()?->format('M Y');
                });

                return match ($this->getOriginal('span') ?? $this->span) {
                    '1st' => '01-15 '.$month->format('M Y'),
                    '2nd' => '16-'.$month->endOfMonth()->day.' '.$month->format('M Y'),
                    'overtime' => $formatted(),
                    'dates' => $formatted(),
                    'range' => $this->from.'-'.$this->to.' '.$month->format('M Y'),
                    default => '01-'.$month->endOfMonth()->day.' '.$month->format('M Y'),
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

    public function scopeCertified(Builder $builder, ?string $level = null): Builder
    {
        return $builder->where(function ($query) {
            $query->where(function ($query) {
                $query->where('span', 'full');

                $query->whereHas('export');
            });

            $query->orWhereIn('span', ['1st', '2nd']);
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
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->orderBy('date');
    }

    public function regularDays(): HasMany
    {
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->regularDays()
            ->orderBy('date');
    }

    public function overtimeWork(): HasMany
    {
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->overtimeWork()
            ->orderBy('date');
    }

    public function firstHalf(): HasMany|HasManyThrough
    {
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->firstHalf()
            ->orderBy('date');
    }

    public function secondHalf(): HasMany
    {
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->secondHalf()
            ->orderBy('date');
    }

    public function customDates(): HasMany
    {
        $month = Carbon::parse($this->month);

        $relationship = $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
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
        return $this->hasMany(Timetable::class, 'timesheet_id', 'timesheet_id')
            ->whereDay('date', '>=', $this->from ?? 1)
            ->whereDay('date', '<=', $this->to ?? Carbon::parse($this->month)->endOfMonth()->day)
            ->orderBy('date');
    }

    public function timetables(): HasMany
    {
        return match ($this->getAttribute('span') ?? $this->span) {
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

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class)
            ->whereColumn('timesheets.id', '!=', 'timesheets.timesheet_id');
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function records()
    {
        return $this->hasManyThrough(Timetable::class, Timesheet::class, 'id', 'timesheets.timesheet_id', null, 'timetables.timesheet_id')
            ->where(function ($query) {
                $query->orWhere(function ($query) {
                    $query->where('timesheets.span', '1st')->whereDay('date', '<=', 15);
                });

                $query->orWhere(function ($query) {
                    $query->where('timesheets.span', '2nd')->whereDay('date', '>', 15);
                });

                $query->orWhere('timesheets.span', 'full');
            });
    }

    public function exports(): MorphMany
    {
        return $this->morphMany(Export::class, 'exportable');
    }

    public function export(): MorphOne
    {
        return $this->exports()
            ->one()
            ->ofMany(['id' => 'max'], function (Builder $query) {
                $query->join('timesheets', 'timesheets.id', 'exports.exportable_id');

                $query->whereColumn('timesheets.span', 'exports.details->period');
            });
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    public function accomplishment(): MorphOne
    {
        return $this->attachments()
            ->one()
            ->ofMany(['id' => 'max'], function (Builder $query) {
                $query->where('classification', AttachmentClassification::ACCOMPLISHMENT);
            });
    }

    public function signers(): HasManyThrough
    {
        return $this->through('export')
            ->has('signers');
    }

    public function signer()
    {
        return $this->signers();
    }
}
