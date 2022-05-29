<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Scanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'attlog_file',
        // 'ip_address',
        // 'protocol',
        // 'serial_number',
        // 'model',
        // 'version',
        // 'library',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
                ->using(EmployeeScanner::class)
                ->withPivot('uid')
                ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                ->using( new class extends Pivot { use HasUniversallyUniqueIdentifier; } )
                ->withTimestamps();
    }
}
