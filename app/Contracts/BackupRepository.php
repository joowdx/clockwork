<?php

namespace App\Contracts;

use App\Models\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

interface BackupRepository
{
    public function backUp(Model|Collection $model): void;

    public function sync(Authenticatable $user): void;

    public function clear(Authenticatable $user): void;
}
