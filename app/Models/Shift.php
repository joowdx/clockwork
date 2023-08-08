<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    use HasUuids;

    const DEFAULT_IN1 = '08:00';

    const DEFAULT_OUT1 = '12:00';

    const DEFAULT_IN2 = '13:00';

    const DEFAULT_OUT2 = '17:00';

    protected $fillable = [
        'in1',
        'in2',
        'out1',
        'out2',
        'default',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'schedules')
            ->using(Schedule::class)
            ->withPivot(['id', 'from', 'to', 'days']);
    }
}
