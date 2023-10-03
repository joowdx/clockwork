<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Crypt;

class Specimen extends Model
{
    use HasUlids;

    protected $fillable = [
        'sample',
    ];

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Signature::class);
    }

    public function sample(): Attribute
    {
        return new Attribute(
            fn ($sample) => Crypt::decryptString($sample),
            fn ($sample) => Crypt::encryptString($sample),
        );
    }
}
