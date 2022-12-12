<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Scanner extends Model
{
    const PRIORITIES = [
        'COLISEUM-1',
        'COLISEUM-2',
        'COLISEUM-3',
        'COLISEUM-4',
    ];

    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'attlog_file',
        'print_text_colour',
        'print_background_colour',
        'remarks',
        'ip_address',
        'protocol',
        'serial_number',
        'model',
        'version',
        'library',
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value ?? '')
        );
    }

    public function printTextColour(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? '#000',
        );
    }

    public function printBackgroundColour(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => in_array(strtolower(@$attributes['print_background_colour'] ?? ''), ['#ffffff', '#fff', null]) ? 'transparent' : strtolower(@$attributes['print_background_colour']),
            set: fn ($value) => in_array(strtolower($value ?? ''), ['#ffffff', '#fff', null]) ? null : strtolower($value),
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
        return $this->hasMany(TimeLog::class)
                ->latest('time')
                ->latest('id');
    }

    public function unrecognized(): HasMany
    {
        return $this->timelogs()
            ->whereNotExists(function ($query) {
                $query->select('uid')
                    ->from('enrollments')
                    ->whereColumn('time_logs.scanner_id', 'enrollments.scanner_id')
                    ->whereColumn('time_logs.uid', 'enrollments.uid');
            });
    }

    public function toSearchableArray(): array
    {
        return [
            $this->getKeyName() => $this->getKey(),
            'name' => $this->name,
        ];
    }
}
