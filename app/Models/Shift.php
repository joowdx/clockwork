<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    use HasUniversallyUniqueIdentifier;

    const DEFAULT_IN1 = '08:00';

    const DEFAULT_IN2 = '12:00';

    const DEFAULT_OUT1 = '13:00';

    const DEFAULT_OUT2 = '17:00';

    protected $fillable = [
        'in1',
        'in2',
        'out1',
        'out2',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'schedules')
            ->using(Schedule::class)
            ->withPivot(['id', 'from', 'to', 'days']);
    }
}
