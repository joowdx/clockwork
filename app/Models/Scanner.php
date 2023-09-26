<?php

namespace App\Models;

use App\Contracts\ScannerDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Scanner extends Model
{
    use HasUlids;
    use Searchable;

    protected $fillable = [
        'name',
        'attlog_file',
        'shared',
        'priority',
        'print_text_colour',
        'print_background_colour',
        'remarks',
        'ip_address',
        'port',
        'driver',
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value ?? '')
        );
    }

    public function printBackgroundColour(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtolower($value) === '#ffffff' || empty($value) ? '#ffffff' : $value
        );
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'enrollments')
            ->using(Enrollment::class)
            ->withPivot(['id', 'uid'])
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignments')
            ->using(Assignment::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function timelogs(): HasMany
    {
        return $this->hasMany(Timelog::class)
            ->latest('time')
            ->latest('id');
    }

    public function unrecognized(): HasMany
    {
        return $this->timelogs()
            ->whereNotExists(function ($query) {
                $query->select('uid')
                    ->from('enrollments')
                    ->whereColumn('timelogs.scanner_id', 'enrollments.scanner_id')
                    ->whereColumn('timelogs.uid', 'enrollments.uid');
            });
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'ip_address' => $this->ip_address,
        ];
    }

    public function getDriverInstance(): ?ScannerDriver
    {
        return null;
    }

    public function getAttlogs(): array
    {
        return $this->getDriverInstance()?->getAttlogs();
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    public function lastUpload(): HasOne
    {
        return $this->hasOne(Upload::class)->latestOfMany('time');
    }

    public function latestTimelog(): HasOne
    {
        return $this->hasOne(Timelog::class)->latestOfMany('time');
    }

    public function scopePriority(Builder $query, bool $priority = true): void
    {
        $query->wherePriority($priority);
    }
}
