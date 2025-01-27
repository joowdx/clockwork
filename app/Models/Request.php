<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RouteAction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $title
 * @property string $body
 * @property RequestStatus $status
 * @property string $remarks
 * @property bool $bypassed
 * @property string $to
 * @property int $step
 * @property int $user_id
 * @property bool $completed
 * @property-read string $toward
 * @property-read User $user
 * @property-read Model $requestable
 * @property-read bool $requested
 * @property-read bool $cancelled
 * @property-read bool $rejected
 * @property-read bool $approved
 * @property-read bool $returned
 * @property-read bool $escalated
 * @property-read bool $deflected
 * @property-read bool $final
 * @property-read bool $ratified
 */

class Request extends MorphPivot
{
    use HasFactory, HasUlids;

    protected $table = 'requests';

    protected $fillable = [
        'title',
        'body',
        'status',
        'remarks',
        'to',
        'for',
        'step',
        'completed',
        'ratified',
        'bypassed',
        'target_id',
        'user_id',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
        'for' => RouteAction::class,
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

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
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
}
