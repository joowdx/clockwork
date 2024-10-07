<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Traits\HasActiveState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Enrollment extends Pivot
{
    use HasActiveState, HasUlids;

    protected $fillable = [
        'uid',
        'employee_id',
        'scanner_id',
        'device',
        'active',
    ];

    public static function booted(): void
    {
        static::saved(fn (self $enrollment) => $enrollment->updateQuietly(['device' => $enrollment->scanner->uid]));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)
            ->withoutGlobalScopes(['excludeInterns', ActiveScope::class]);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class)
            ->withoutGlobalScopes(['excludeInterns', ActiveScope::class]);
    }
}
