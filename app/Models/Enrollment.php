<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Builder;

class Enrollment extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    protected $table = 'enrollments';

    protected $touches = ['employee'];

    protected $fillable = ['uid', 'enabled'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class);
    }

    public function timelogs(): HasMany
    {
        return $this->hasMany(Timelog::class);
    }

    public function scopeEnabled(Builder $query)
    {
        return $query->where('enabled', true);
    }
}
