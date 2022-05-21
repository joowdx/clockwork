<?php

namespace App\Models;

use Awobaz\Compoships\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'protocol',
        'serial_number',
        'model',
        'version',
        'library',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
