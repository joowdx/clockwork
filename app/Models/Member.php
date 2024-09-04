<?php

namespace App\Models;

use App\Traits\HasActiveState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Member extends Pivot
{
    use HasActiveState, HasUlids;

    protected $fillable = [
        'group_id',
        'employee_id',
        'active',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)
            ->withoutGlobalScopes();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)
            ->withoutGlobalScopes();
    }
}
