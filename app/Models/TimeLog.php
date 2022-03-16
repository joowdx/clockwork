<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use Compoships;
    use HasFactory;

    const IN = ['1000', '1010'];
    const OUT = ['1100', '1110'];

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
