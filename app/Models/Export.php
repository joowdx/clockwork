<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use function Safe\stream_get_contents;

class Export extends Model
{
    use HasFactory, HasUlids, Prunable;

    protected $fillable = [
        'digest',
        'filename',
        'exception',
        'content',
        'status',
        'downloads',
        'downloaded_at',
    ];

    protected $casts = [
        'details' => 'object',
    ];

    public static function booted()
    {
        static::saved(function (Export $export) {
            $export->updateQuietly(['digest' => $export->content !== null ? hash('sha512', $export->content) : null]);
        });
    }

    public function content(): Attribute
    {
        return Attribute::make(
            fn (mixed $content): ?string => is_null($content) ? null : base64_decode(stream_get_contents($content)),
            fn (mixed $content): mixed => is_null($content) ? null : base64_encode($content),
        )->shouldCache();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function prunable()
    {
        return static::query()
            ->orWhere(fn ($query) => $query->whereNotNull('exportable_type')->whereNotNull('exportable_id')->whereDoesntHave('exportable'))
            ->orWhere(fn ($query) => $query->whereNotNull('user_id')->where('created_at', '<=', now()->subMinutes(15)));
    }

    public function scopeTimesheet(Builder $query): void
    {
        $query->where('exportable_type', Timesheet::class);
    }
}
