<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['user'];

    protected function init(Builder &$builder): void
    {
        $builder->sortByName();
    }

    public function transformImportData(array $line, array $headers): array
    {
        return $this->transformData([
            'biometrics_id' => $line[$headers['SCANNER ID']],
            'name' => [
                'last' => $line[$headers['FAMILY NAME']],
                'first' => $line[$headers['GIVEN NAME']],
                'middle' => $line[$headers['MIDDLE INITIAL']],
                'extension' => $line[$headers['NAME EXTENSION']],
            ],
            'office' => $line[$headers['OFFICE']],
            'regular' => (bool) $line[$headers['REGULAR']],
            'active' => (bool) $line[$headers['ACTIVE']],
            'user_id' => request()->user()->id,
        ]);
    }

    protected function transformData(array $payload): array
    {
        return [
            'biometrics_id' => $payload['biometrics_id'],
            'name' => json_encode([
                'last' => $payload['name']['last'],
                'first' => $payload['name']['first'],
                'middle' => $payload['name']['middle'],
                'extension' => $payload['name']['extension'],
            ]),
            'office' => $payload['office'],
            'regular' => (bool) $payload['regular'],
            'active' => (bool) $payload['active'],
            'user_id' => $payload['user_id'],
        ];
    }
}
