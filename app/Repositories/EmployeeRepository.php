<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['scanners'];

    protected function init(Builder &$builder): void
    {
        $builder->sortByName();
    }

    public function transformImportData(array $line, array $headers): array
    {
        return [
            'scanner_uid' => $this->parseScannerUID($line[$headers['SCANNER UID']]),
            'name' => [
                'last' => $line[$headers['LAST NAME']],
                'first' => $line[$headers['FIRST NAME']],
                'middle' => @$line[$headers['MIDDLE INITIAL']],
                'extension' => @$line[$headers['NAME EXTENSION']],
            ],
            'office' => @$line[$headers['OFFICE']],
            'regular' => (bool) $line[$headers['REGULAR']],
            'active' => (bool) @$line[$headers['ACTIVE']],
            'nameToJSON' => true,
        ];
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
            'office' => strtoupper($payload['office']),
            'regular' => (bool) $payload['regular'],
            'active' => (bool) @$payload['active'] ?? null,
        ];
    }

    private function parseScannerUID(string $scanner_uid)
    {
        return collect(str_getcsv($scanner_uid))
                ->map(fn ($uid) => explode(':', $uid))
                ->mapWithKeys(fn ($uid) => [$uid[0] => $uid[1]])
                ->toArray();
    }
}
