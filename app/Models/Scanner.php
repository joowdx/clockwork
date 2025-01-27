<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Traits\Assignmentable;
use App\Traits\HasActiveState;
use App\Traits\HasActivityLogs;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scanner extends Model implements Auditable
{
    use Assignmentable, HasActiveState, HasActivityLogs, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'uid',
        'print',
        'remarks',
        'shared',
        'priority',
        'host',
        'port',
        'pass',
        'active',
        'synced_at',
    ];

    protected $casts = [
        'print' => 'json',
        'synced_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (self $scanner) {
            if ($scanner->wasChanged('uid')) {
                $scanner->enrollments()->update(['device' => $scanner->uid]);
            }
        });
    }

    public function foregroundColor(): Attribute
    {
        return Attribute::make(
            fn () => $this->print['foreground_color'] ?? 'rgba(0, 0, 0, 1)',
        );
    }

    public function backgroundColor(): Attribute
    {
        return Attribute::make(
            fn () => $this->print['background_color'] ?? 'rgba(0, 0, 0, 0)',
        );
    }

    public function fontSize(): Attribute
    {
        return Attribute::make(
            fn () => $this->print['font_size'] ?? 12,
        );
    }

    public function fontStyle(): Attribute
    {
        return Attribute::make(
            fn () => $this->print['font_style'] ?? 'normal',
        );
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'enrollment')
            ->using(Enrollment::class)
            ->withPivot('uid', 'active')
            ->wherePivot('active', true);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function users(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', Assignment::class)
            ->using(Assignment::class)
            ->withPivot('active')
            ->wherePivot('active', true);
    }

    public function assignees(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable')
            ->where('active', true);
    }

    public function timelogs(): HasMany
    {
        return $this->hasMany(Timelog::class, 'device', 'uid')
            ->latest('time')
            ->latest('id');
    }

    public function latestTimelog(): HasOne
    {
        return $this->timelogs()
            ->reorder()
            ->one()
            ->ofMany('time');
    }

    public function lastSync(): MorphOne
    {
        return $this->activities()
            ->one()
            ->ofMany('time')
            ->where(fn ($query) => $query->whereIn('data->action', ['import', 'fetch']));
    }
}
