<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Enrollment extends Pivot
{
    use HasUniversallyUniqueIdentifier;

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
        return $this->hasMany(TimeLog::class);
    }
}
