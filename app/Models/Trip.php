<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Trip extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    protected $table = 'trips';
}
