<?php

namespace App\Models;

class Schedule extends Model
{
    const DEFAULT_IN = '08';
    const DEFAULT_OUT = '16';

    protected $fillable = [
        'from',
        'to',
        'in',
        'out',
        'employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
