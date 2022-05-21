<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class ScannerRepository extends BaseRepository
{
    protected array $with = ['user'];

    protected function init(Builder &$builder): void
    {
        $builder->orderBy('name');
    }

    protected function transformData(array $payload): array
    {
        return $payload;
    }
}
