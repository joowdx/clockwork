<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;

class TimeLogRepository extends BaseRepository
{
    protected function transformData(array $payload): array
    {
        return [
            'uid' => $payload['uid'],
            'scanner_id' => $payload['scanner_id'],
            'time' => $payload['time'],
            'state' => $payload['state'],
        ];
    }
}
