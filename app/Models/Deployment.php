<?php

namespace App\Models;

use App\Traits\HasActiveState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Deployment extends Pivot
{
    use HasActiveState, HasUlids;

    protected $fillable = [
        'current',
        'active',
        'office_id',
        'employee_id',
        'supervisor_id',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }
}
