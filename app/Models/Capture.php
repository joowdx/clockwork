<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capture extends Model
{
    use HasUlids;

    protected $fillable = [
        'status',
        'command',
        'pid',
        'runtime',
        'result',
        'uuid',
    ];

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class);
    }
}
