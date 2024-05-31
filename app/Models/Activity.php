<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'activitable_type',
        'activitable_id',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function time(): Attribute
    {
        return Attribute::make(fn () => $this->created_at);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activitable(): MorphTo
    {
        return $this->morphTo();
    }
}
