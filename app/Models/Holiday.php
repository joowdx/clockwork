<?php

namespace App\Models;

use App\Enums\HolidayType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'type',
        'date',
        'from',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'type' => HolidayType::class,
        'date' => 'datetime:Y-m-d',
        'from' => 'datetime:H:i',
    ];

    public static function search(Carbon $date, bool $all = true): Collection|self|null
    {
        return cache()->remember(
            'holiday-'.$date->format('Y-m-d').($all ? 'all' : 'one'), 60,
            fn () => static::query()->whereDate('date', $date)->{$all ? 'get' : 'first'}()
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
