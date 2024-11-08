<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Traits\FormatsName;
use App\Traits\HasActiveState;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Employee extends Model implements \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\CanResetPassword, \Illuminate\Contracts\Auth\MustVerifyEmail, FilamentUser, HasName
{
    use Authenticatable, CanResetPassword, FormatsName, HasActiveState, HasFactory, HasUlids, MustVerifyEmail, Notifiable, SoftDeletes;

    protected $fillable = [
        'prefix_name',
        'suffix_name',
        'first_name',
        'middle_name',
        'last_name',
        'qualifier_name',
        'email',
        'number',
        'password',
        'sex',
        'birthdate',
        'designation',
        'status',
        'substatus',
        'uid',
        'active',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'active' => 'boolean',
        'status' => EmploymentStatus::class,
        'substatus' => EmploymentSubstatus::class,
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('excludeInterns', function (Builder $builder) {
            $builder->whereNot('status', EmploymentStatus::INTERNSHIP);
        });

        static::creating(function (Employee $employee) {
            $employee->uid = $employee->uid ?: fake()->valid(fn ($uid) => static::whereUid($uid)->doesntExist())->bothify('?????###');
        });
    }

    public function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($first) => $this->formatName($first),
            set: fn ($first) => $this->formatName($first),
        )->shouldCache();
    }

    public function middleName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $middle): string => $this->formatName($middle, true) ?: 'N/A',
            set: fn (?string $middle): ?string => empty($middle) || $middle === 'N/A' ? '' : $this->formatName($middle, true),
        )->shouldCache();
    }

    public function lastName(): Attribute
    {
        return Attribute::make(
            get: fn ($last) => $this->formatName($last),
            set: fn ($last) => $this->formatName($last),
        )->shouldCache();
    }

    public function qualifierName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $qualifier): string => empty($qualifier) ? 'N/A' : $this->formatName($qualifier, true),
            set: fn (?string $qualifier): ?string => empty($qualifier) || $qualifier === 'N/A' ? '' : $this->formatName($qualifier, true),
        )->shouldCache();
    }

    public function middleInitial(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) str($this->middle_name !== 'N/A' ? $this->middle_name : null)
                ->substr(0, 1)
                ->append('.')
                ->ltrim('.')
                ->upper(),
        )->shouldCache();
    }

    public function titledName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) str($this->prefix_name)
                ->append(' ')
                ->append($this->first_name)
                ->append(' ')
                ->append($this->middle_initial)
                ->append(' ')
                ->append($this->last_name)
                ->append($this->qualifier_name !== 'N/A' ? ", $this->qualifier_name" : null)
                ->append($this->suffix_name ? ", {$this->suffix_name}" : null)
                ->squish()
                ->trim(),
        )->shouldCache();
    }

    public function sex(): Attribute
    {
        return Attribute::make(
            function ($sex): ?string {
                if (! in_array(strtolower($sex), ['m', 'f', 'male', 'female'])) {
                    return null;
                }

                return in_array(strtolower($sex), ['m', 'male']) ? 'male' : 'female';
            }
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn (): int => now()->diffInYears($this->birthdate),
        );
    }

    public function uid(): Attribute
    {
        return Attribute::make(
            get: fn (?string $uid): ?string => $uid ? (string) str($uid)->lower() : null,
            set: fn (?string $uid): ?string => $uid ? (string) str($uid)->lower() : null,
        )->shouldCache();
    }

    public function regular(): Attribute
    {
        return Attribute::make(
            fn () => $this->status !== EmploymentStatus::CONTRACTUAL,
        );
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'deployment')
            ->using(Deployment::class)
            ->withPivot(['active', 'current'])
            ->orderByPivot('current', 'desc')
            ->wherePivot('active', true);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class)
            ->orderBy('deployment.current', 'desc');
    }

    public function office(): HasOneThrough
    {
        return $this->hasOneThrough(Office::class, Deployment::class, 'employee_id', 'id', 'id', 'office_id')
            ->where('current', true);
    }

    public function currentOffice(): HasOneThrough
    {
        return $this->hasOneThrough(Office::class, Deployment::class, 'employee_id', 'id', 'id', 'office_id')
            ->where('current', true);
    }

    public function currentDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)
            ->ofMany()
            ->where('current', true);
    }

    public function supervisor(): HasOneThrough
    {
        return $this->hasOneThrough(Employee::class, Deployment::class, 'employee_id', 'id', 'office_id', 'supervisor_id')
            ->where('current', true);
    }

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class, 'enrollment')
            ->using(Enrollment::class)
            ->withPivot(['uid', 'active'])
            ->wherePivot('active', true);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'member')
            ->using(Member::class)
            ->withPivot('active')
            ->orderBy('name')
            ->wherePivot('active', true);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'shift')
            ->using(Shift::class)
            ->withPivot('timetable')
            ->whereHas('request', fn ($query) => $query->where('completed', true))
            ->whereGlobal(false);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(Timelog::class, Enrollment::class, 'employees.id', 'uid', secondLocalKey: 'uid')
            ->join('scanners', fn ($join) => $join->on('scanners.uid', 'timelogs.device')->on('scanners.id', 'enrollment.scanner_id'))
            ->join('employees', 'employees.id', 'enrollment.employee_id')
            ->whereColumn('timelogs.uid', 'enrollment.uid')
            ->whereColumn('employees.id', 'enrollment.employee_id')
            ->where('enrollment.active', true)
            ->latest('time')
            ->latest('timelogs.id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function timetables(): HasManyThrough
    {
        return $this->hasManyThrough(Timetable::class, Timesheet::class, 'employee_id', 'timesheet_id')
            ->latest('date');
    }

    public function signature(): MorphOne
    {
        return $this->morphOne(Signature::class, 'signaturable');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'employee';
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->middle_initial} {$this->last_name}";
    }
}
