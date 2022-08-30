<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;

class Scanner extends Model
{
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

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, Enrollment::class, secondKey: 'enrollment_id')
                ->latest('time')
                ->latest('id');
    }

    public function unrecognized(): HasMany
    {
        return $this->hasMany(TimeLog::class)
                ->latest('time')
                ->latest('id')
                ->whereNull('enrollment_id');
    }

    public function toSearchableArray(): array
    {
        return [
            $this->getKeyName() => $this->getKey(),
            'name' => $this->name,
        ];
    }
}
