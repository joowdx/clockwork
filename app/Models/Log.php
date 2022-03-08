<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometrics_id',
        'time',
        'state',
    ];

    protected $casts = [
        'data' => 'object',
        'time' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'biometrics_id', 'biometrics_id');
    }
}
