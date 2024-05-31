<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'model',
        'type',
        'path',
        'escalation',
    ];

    protected $casts = [
        'path' => 'json',
        'escalation' => 'json',
    ];

    public function next(?int $current): ?string
    {
        if ($current === null) {
            return $this->path[0] ?? null;
        }

        return $this->path[$current] ?? null;
    }

    public function final(bool $step = false): string|int|null
    {
        return $step ? count($this->path) : $this->path[count($this->path) - 1] ?? null;
    }
}
