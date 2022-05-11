<?php

namespace App\Models\SQLite;

use App\Models\Model;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    public $timestamps = false;

    protected $connection = 'sqlite';

    protected $fillable = [
        'employee_id',
        'active',
        'persist',
    ];

    protected $hidden = [
        'active',
        'persist',
    ];

    protected function scopeActive(Builder $query, bool $active = true)
    {
        $query->whereActive($active);
    }

    protected function scopePersist(Builder $query, bool $persist = true)
    {
        $query->wherePersist($persist);
    }

    protected function scopeTemporary(Builder $query)
    {
        $query->persist(false);
    }
}
