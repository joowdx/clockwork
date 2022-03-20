<?php

namespace App\Models;

use App\Traits\HasNameAccessorAndFormatter;
use Awobaz\Compoships\Compoships;
use Awobaz\Compoships\Database\Eloquent\Relations\BelongsTo;
use Awobaz\Compoships\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Employee extends Model
{
    use Compoships;
    use HasFactory;
    use HasNameAccessorAndFormatter;
    use Searchable;

    protected $fillable = [
        'biometrics_id',
        'name',
        'regular',
        'office',
        'user_id',
    ];

    protected $casts = [
        'name' => 'object',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TimeLog::class, ['biometrics_id', 'user_id'], ['biometrics_id', 'user_id']);
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name_format->full,
        ];
    }

    public function scopeRegular(Builder $query): void
    {
        $query->where('regular', 1);
    }

    public function scopeNonRegular(Builder $query): void
    {
        $query->where('regular', 0);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', 1);
    }

    public function scopeInactive(Builder $query): void
    {
        $query->where('active', 0);
    }

}
