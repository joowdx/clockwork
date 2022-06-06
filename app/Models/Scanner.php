<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Scanner extends Model
{
    use HasFactory;

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

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'enrollments')
                ->using(Enrollment::class)
                ->withPivot('uid')
                ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignments')
                ->using(Assignment::class)
                ->withTimestamps();
    }

    public function timelogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, Enrollment::class, secondKey: 'enrollment_id');
    }
}
