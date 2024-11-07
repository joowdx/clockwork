<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Signer extends Pivot
{
    use HasUlids;

    protected $with = ['signer'];

    protected $fillable = [
        'meta',
        'export_id',
        'signer_type',
        'signer_id',
    ];

    public function signer(): MorphTo
    {
        return $this->morphTo();
    }
}
