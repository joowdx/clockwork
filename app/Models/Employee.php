<?php

namespace App\Models;

use App\Traits\HasNameAccessorAndFormatter;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Employee extends Model
{
    use Compoships;
    use HasFactory;
    use HasNameAccessorAndFormatter;
    use Searchable;

    protected $fillable = [
        'biometrics_id',
        'name',
        'regular',
        'office',
        'user_id',
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

    public function toSearchableArray()
    {
        return [
            'name' => $this->name_format->full,
        ];
    }

}
