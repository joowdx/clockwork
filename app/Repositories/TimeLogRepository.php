<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Carbon\Carbon;

class TimeLogRepository extends BaseRepository
{
    public function transformImportData(array $record): array
    {
        return [
            'uid' => trim($record[0]),
            'time' => Carbon::createFromTimeString($record[1]),
            'state' => join('', collect(array_slice($record, 2))->map(fn ($record) => $record > 1 ? 1 : $record)->toArray()),
        ];
    }

    protected function transformData(array $payload): array
    {
        return [
            'uid' => $payload['scanner_id'],
            'time' => $payload['time'],
            'state' => $payload['state'],
        ];
    }
}
