<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use Compoships;
    use HasFactory;

    protected $fillable = [
        'biometrics_id',
        'user_id',
        'time',
        'state',
    ];

    protected $casts = [
        'data' => 'object',
        'time' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'biometrics_id', 'biometrics_id')->where('user_id', $this->user_id);
    }
}
