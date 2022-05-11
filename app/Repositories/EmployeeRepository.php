<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['user'];

    protected function init(Builder &$builder): void
    {
        $builder->sortByName();
    }

    public function transformImportData(array $line, array $headers): array
    {
        return [
            'biometrics_id' => $line[$headers['SCANNER ID']],
            'name' => [
                'last' => $line[$headers['FAMILY NAME']],
                'first' => $line[$headers['GIVEN NAME']],
                'middle' => @$line[$headers['MIDDLE INITIAL']],
                'extension' => @$line[$headers['NAME EXTENSION']],
            ],
            'office' => @$line[$headers['OFFICE']],
            'regular' => (bool) $line[$headers['REGULAR']],
            'active' => (bool) @$line[$headers['ACTIVE']],
            'user_id' => auth()->id(),
            'nameToJSON' => true,
        ];
    }

    protected function transformData(array $payload): array
    {
        $name = [
            'last' => strtoupper($payload['name']['last']),
            'first' => strtoupper($payload['name']['first']),
            'middle' => strtoupper($payload['name']['middle']),
            'extension' => strtoupper($payload['name']['extension']),
        ];

        return [
            'biometrics_id' => $payload['biometrics_id'],
            'name' => @$payload['nameToJSON'] ? json_encode($name) : $name,
            'office' => strtoupper($payload['office']),
            'regular' => (bool) $payload['regular'],
            'active' => ((bool) @$payload['active']) ?? null,
            'user_id' => $payload['user_id'],
        ];
    }
}
