<?php

namespace App\Models;

use App\Enums\AnnotationField;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Annotation extends Model
{
    use HasUlids;

    protected $fillable = [
        'date',
        'to',
        'field',
        'note',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'to' => 'date:Y-m-d',
        'field' => AnnotationField::class,
    ];

    public function timesheet()
    {
        return $this->belongsTo(Timesheet::class);
    }
}
