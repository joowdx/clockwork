<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Crypt;

class Specimen extends Model
{
    use HasUlids;

    protected $fillable = [
        'sample',
        'mime',
        'checksum',
        'enabled',
    ];

    private $stream;

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Signature::class);
    }

    public function scopeEnabled(Builder $query, bool $enabled = true): void
    {
        $query->whereEnabled($enabled);
    }

    public function sample(): Attribute
    {
        return new Attribute(
            fn ($sample) => Crypt::decryptString($this->stream ??= stream_get_contents($sample)),
            fn ($sample) => Crypt::encryptString($sample),
        );
    }

    public function verify(): bool
    {
        return hash('sha3-256', $this->sample) === $this->checksum;
    }

    public function toSrc(): ?string
    {
        if ($this->verify()) {
            return "data:$this->mime;base64,".$this->toBase64();
        }
    }

    public function toBase64(): ?string
    {
        if ($this->verify()) {
            return base64_encode($this->sample);
        }
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        if (array_key_exists('sample', $data)) {
            return [
                ...$data,
                'sample' => $this->toBase64(),
            ];
        }

        return $data;
    }
}
