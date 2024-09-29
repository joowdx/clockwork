<?php

namespace App\Models;

use App\Traits\TimelogsHasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;

class Timetable extends Model
{
    use HasFactory, HasUlids;
    use TimelogsHasher;

    protected $fillable = [
        'date',
        'punch',
        'undertime',
        'overtime',
        'duration',
        'half',
        'absent',
        'present',
        'invalid',
        'holiday',
        'regular',
        'rectified',
        'timesheet_id',
        'digest',
    ];

    protected $casts = [
        'half' => 'bool',
        'absent' => 'bool',
        'present' => 'bool',
        'invalid' => 'bool',
        'regular' => 'bool',
        'date' => 'date:Y-m-d',
        'punch' => 'json',
    ];

    protected $touches = ['timesheet'];

    public function undertime(): Attribute
    {
        return Attribute::make(
            fn ($undertime) => $undertime > 0 ? $undertime : null,
        );
    }

    public function overtime(): Attribute
    {
        return Attribute::make(
            fn ($overtime) => $overtime > 0 ? $overtime : null,
        );
    }

    public function duration(): Attribute
    {
        return Attribute::make(
            fn ($duration) => $duration > 0 ? $duration : null,
        );
    }

    public function holiday(): Attribute
    {
        return Attribute::make(
            set: fn ($holiday) => $holiday ?: null,
        );
    }

    public function period(): Attribute
    {
        return Attribute::make(
            fn () => $this->date->day <= 15 ? '1st' : '2nd',
        );
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(Employee::class, Timesheet::class, 'timetables.id', 'id', secondLocalKey: 'employee_id')
            ->join('timetables', 'timesheets.id', 'timetables.timesheet_id');
    }

    public function scopeOvertimeWork(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where('present', true)->where('regular', false);
        });

        $query->orWhere(function (Builder $query) {
            $query->where('overtime', '>', 0)->where('regular', true);
        });
    }

    public function scopeRegularDays(Builder $query): void
    {
        $query->where('regular', 1);
    }

    public function scopeFirstHalf(Builder $query): void
    {
        $query->whereDay('date', '<=', 15);
    }

    public function scopeSecondHalf(Builder $query): void
    {
        $query->whereDay('date', '>=', 16);
    }

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(Timelog::class, Enrollment::class, 'timetables.id', 'uid', secondLocalKey: 'uid')
            ->join('scanners', fn ($join) => $join->on('scanners.uid', 'timelogs.device')->on('scanners.id', 'enrollment.scanner_id'))
            ->join('timesheets', 'timesheets.employee_id', 'enrollment.employee_id')
            ->join('timetables', 'timetables.timesheet_id', 'timesheets.id')
            ->whereColumn(DB::raw('DATE(timelogs.time)'), 'timetables.date')
            ->whereColumn('timelogs.uid', 'enrollment.uid')
            ->whereColumn('timesheets.employee_id', 'enrollment.employee_id')
            ->where('enrollment.active', true)
            ->latest('time')
            ->latest('timelogs.id');
    }
}
