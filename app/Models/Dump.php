<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class Dump extends Model
{
    use HasFactory, HasUlids, Prunable;

    protected $fillable = [
        'path',
        'exception',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subYears(2));
    }

    protected function pruning()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
