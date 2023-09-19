<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Timelog extends Model
{
    use HasUuids;

    const GRACE_PERIOD = 15;

    const IN = [
        1000,
        1010,
        0000,
        0004,
    ];

    const OUT = [
        1100,
        1110,
        0001,
        0005,
    ];

    public int $undertime;

    protected $fillable = [
        'scanner_id',
        'user_id',
        'time',
        'state',
        'hidden',
    ];

    protected $hidden = [
        'hidden',
    ];

    protected $casts = [
        'time' => 'datetime',
    ];

    protected $appends = [
        'type',
    ];

    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(Employee::class, Enrollment::class, 'timelogs.id', 'id', secondLocalKey: 'employee_id')
            ->join($this->getTable(), function ($join) {
                $join->on('enrollments.uid', 'timelogs.uid');
                $join->on('enrollments.scanner_id', 'timelogs.scanner_id');
            });
    }

    public function scanner(): HasOneThrough
    {
        return $this->hasOneThrough(Scanner::class, Enrollment::class, 'scanners.id', 'id', 'scanner_id', 'scanner_id');
    }

    public function scopeUnrecognized(Builder $query): void
    {
        $query->whereNull('enrollment_id');
    }

    protected function getArrayableAppends(): array
    {
        $appends = array_merge($this->appends, ['in', 'out']);

        return $this->getArrayableItems(
            array_combine($appends, $appends)
        );
    }

    public function getTypeAttribute(): string
    {
        return $this->in ? 'In' : ($this->out ? 'Out' : '**');
    }

    public function getInAttribute(): bool
    {
        return $this->isTimeIn();
    }

    public function getOutAttribute(): bool
    {
        return $this->isTimeOut();
    }

    public function getTardyAttribute(): ?bool
    {
        return $this->isTardy();
    }

    public function getUnderTimeAttribute(): ?bool
    {
        return $this->isUnderTime();
    }

    public function getProperAttribute(): ?bool
    {
        return $this->isTardy();
    }

    public function getUnderGracePeriodAttribute(): ?bool
    {
        return $this->isUnderGracePeriod();
    }

    public function getAcceptableAttribute(): ?bool
    {
        return $this->isUnderGracePeriod();
    }

    public function isTimeIn(): bool
    {
        return in_array($this->state, self::IN);
    }

    public function isTimeOut(): bool
    {
        return in_array($this->state, self::OUT);
    }

    public function isTardy(): ?bool
    {
        return $this->in ? $this->time->clone()->setTime($this->employee->getSchedule($this->time)->in, '01')->lte($this->time) : null;
    }

    public function isUnderTime(): ?bool
    {
        return $this->out ? $this->time->clone()->setHour($this->employee->getSchedule($this->time)->out)->gt($this->time) : null;
    }

    public function isUnderGracePeriod(): mixed
    {
        return $this->in ? $this->time->clone()->setTime($this->employee->getSchedule($this->time)->in, 0)->diffInMinutes($this->time) <= self::GRACE_PERIOD : null;
    }

    public function isSame(self $timeLog, bool $strict = false): bool
    {
        return $this->time->{$strict ? 'eq' : 'isSameDay'}($timeLog)
            && $this->state === $timeLog->state
            && $this->employee_id === $timeLog->employee_id
            && $this->user_id === $timeLog->user_id;
    }
}

enum TimeLogStates
{
    case IN1;
    case IN2;
    case OUT1;
    case OUT2;
}
