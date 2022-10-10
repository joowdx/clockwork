<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['scanners'];

    protected function init(Builder &$builder): void
    {
        $builder->sortByName();
    }

    protected function transformData(array $payload): array
    {
        $name = [
            'last' => strtoupper($payload['name']['last']),
            'first' => strtoupper($payload['name']['first']),
            'middle' => strtoupper(@$payload['name']['middle']),
            'extension' => strtoupper(@$payload['name']['extension']),
        ];

        return [
            'name' => @$payload['nameToJSON'] ? json_encode($name) : $name,
            'office' => strtoupper(@$payload['office']),
            'regular' => (bool) $payload['regular'],
            'active' => @$payload['active'] === null ? true : (bool) @$payload['active'],
            'csc_format' => (bool) @$payload['csc_format'] ?? null,
        ];
    }
}
