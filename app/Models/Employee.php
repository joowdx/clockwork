<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use Compoships;
    use HasFactory;

    protected $fillable = [
        'biometrics_id',
        'name',
        'regular',
        'user_id'
    ];

    protected $casts = [
        'name' => 'object',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(TimeLog::class, ['biometrics_id', 'user_id'], ['biometrics_id', 'user_id']);
    }

    public function getFullNameAttribute()
    {
        return "{$this->name->last}, {$this->name->first} {$this->name->extension}";
    }


}
