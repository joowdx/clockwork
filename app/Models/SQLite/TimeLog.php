<?php

namespace App\Models\SQLite;

use App\Models\TimeLog as Model;
use Illuminate\Database\Eloquent\Builder;

class TimeLog extends Model
{
    public $timestamps = false;

    protected $connection = 'sqlite';

    protected $fillable = [
        'employee_id',
        'time_log_id',
        'time',
        'state',
        'persist',
    ];

    protected $with = [];

    protected function scopePersist(Builder $query, bool $persist = true)
    {
        $query->wherePersist($persist);
    }

    protected function scopeTemporary(Builder $query)
    {
        $query->persist(false);
    }
}
