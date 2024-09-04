<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dump extends Model
{
    use HasFactory, HasUlids, Prunable, SoftDeletes;

    protected $fillable = [
        'file',
        'exception',
        'size',
    ];

    protected static function booted()
    {
        static::forceDeleted(function (self $dump) {
            if ($dump->stored) {
                unlink($dump->path);
            }
        });
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subYears(2));
    }

    protected function pruning()
    {
        if ($this->stored) {
            unlink($this->path);
        }
    }

    public function path(): Attribute
    {
        return Attribute::make(fn () => base_path('database/dumps/'.$this->file));
    }

    public function stored(): Attribute
    {
        return Attribute::make(fn () => $this->file && file_exists($this->path));
    }
}
