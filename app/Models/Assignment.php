<?php

namespace App\Models;

use App\Traits\HasActiveState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Assignment extends MorphPivot
{
    use HasActiveState, HasUlids;

    protected $table = 'assignments';

    protected $fillable = [
        'user_id',
        'active',
        'assignable_type',
        'assignable_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}
