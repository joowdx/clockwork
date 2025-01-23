<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Request extends MorphPivot
{
    use HasFactory, HasUlids;

    protected $table = 'requests';

    protected $fillable = [
        'title',
        'body',
        'status',
        'remarks',
        'bypassed',
        'to',
        'step',
        'user_id',
        'completed',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
    ];

    public function to(): Attribute
    {
        return Attribute::make(
            fn (?string $to) => settings($to) ?? $to,
        )->shouldCache();
    }

    public function toward(): Attribute
    {
        return Attribute::make(
            fn () => $this->attributes['to'],
        )->shouldCache();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requested(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::REQUEST,
        )->shouldCache();
    }

    public function cancelled(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::CANCEL,
        )->shouldCache();
    }

    public function rejected(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::REJECT,
        )->shouldCache();
    }

    public function approved(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::APPROVE,
        )->shouldCache();
    }

    public function returned(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::RETURN,
        )->shouldCache();
    }

    public function escalated(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::ESCALATE,
        )->shouldCache();
    }

    public function deflected(): Attribute
    {
        return Attribute::make(
            fn () => $this->status === RequestStatus::DEFLECT,
        )->shouldCache();
    }

    public function final(): Attribute
    {
        return Attribute::make(
            fn () => $this->step === count($this->requestable->route->path),
        )->shouldCache();
    }

    public function completelyApproved(): Attribute
    {
        return Attribute::make(
            fn () => $this->approved && $this->final,
        )->shouldCache();
    }
}
