<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Signer extends Pivot
{
    use HasUlids;

    protected $with = ['signable', 'signer'];

    protected $fillable = [
        'meta',
        'field',
        'signable_type',
        'signable_id',
        'signer_type',
        'signer_id',
    ];

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signer(): MorphTo
    {
        return $this->morphTo();
    }
}
