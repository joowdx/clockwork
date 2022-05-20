<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Carbon\Carbon;

class TimeLogRepository extends BaseRepository
{
    public function transformImportData(array $record): array
    {
        return [
            'scanner_id' => trim($record[0]),
            'time' => Carbon::createFromTimeString($record[1]),
            'state' => join('', collect(array_slice($record, 2))->map(fn ($record) => $record > 1 ? 1 : $record)->toArray()),
            'user_id' => request()->user()->id,
        ];
    }

    protected function transformData(array $payload): array
    {
        return [
            'scanner_id' => $payload['scanner_id'],
            'time' => $payload['time'],
            'state' => $payload['state'],
            'user_id' => $payload['user_id'],
        ];
    }
}
