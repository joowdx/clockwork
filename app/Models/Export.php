<?php

namespace App\Models;

use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory, HasUlids;

    protected $casts = [
        'options' => 'object',
    ];

    public function success(): Attribute
    {
        return Attribute::make(fn () => $this->status !== ExportStatus::FAILED);
    }

    public function failed(): Attribute
    {
        return Attribute::make(fn () => $this->status === ExportStatus::FAILED);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exportable()
    {
        return $this->morphTo();
    }
}
