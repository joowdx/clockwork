<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeScanner extends Pivot
{
    use HasUniversallyUniqueIdentifier;

    public $timestamps = true;

    protected $touches = ['employee'];

    protected $fillable = [
        'scanner_uid',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class);
    }

}
