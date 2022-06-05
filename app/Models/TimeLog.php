<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TimeLog extends Model
{
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

    protected $fillable = [
        'biometrics_id',
        'user_id',
        'time',
        'state',
    ];

    protected $casts = [
        'time' => 'datetime'
    ];

    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(Employee::class, EmployeeScanner::class, 'id', 'id', 'employee_scanner_id', 'employee_id');
    }

    public function scanner(): HasOneThrough
    {
        return $this->hasOneThrough(Scanner::class, EmployeeScanner::class, 'id', 'id', 'employee_scanner_id', 'scanner_id');
    }

    public function backup(): HasOne
    {
        return $this->hasOne(BackupTimeLog::class);
    }

    protected function getArrayableAppends(): array
    {
        $appends = array_merge($this->appends, ['in', 'out']);

        return $this->getArrayableItems(
            array_combine($appends, $appends)
        );
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

    public function getBackedUpAttribute(): ?bool
    {
        return $this->isBackedUp();
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

    public function isBackedUp(): bool
    {
        return $this->backup !== null;
    }

    public function isSame(self $timeLog, bool $strict = false): bool
    {
        return $this->time->{$strict ? 'eq' : 'isSameDay'}($timeLog)
            && $this->state === $timeLog->state
            && $this->employee_id === $timeLog->employee_id
            && $this->user_id === $timeLog->user_id;
    }
}
