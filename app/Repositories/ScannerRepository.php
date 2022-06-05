<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class ScannerRepository extends BaseRepository
{
    protected array $with = ['users'];

    protected function init(Builder &$builder): void
    {
        $builder->orderBy('name');
    }

    protected function transformData(array $payload): array
    {
        return [
            'name' => strtolower($payload['name']),
            'attlog' => $payload['attlog'],
            'color' => $payload['color'],
            'remarks' => $payload['remarks'],
            'shared' => (bool) $payload['remarks'],
            'ip_address' => $payload['ip_address'],
            'protocol' => $payload['protocol'],
            'library' => $payload['library'],
        ];
    }
}
