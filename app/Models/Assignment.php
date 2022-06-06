<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Assignment extends Pivot
{
    use HasUniversallyUniqueIdentifier;

    public $timestamps = true;

    protected $table = 'assignments';

    protected $touches = ['user', 'scanner'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scanner()
    {
        return $this->belongsTo(Scanner::class);
    }
}
