<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;

class TimeLogRepository extends BaseRepository
{
    protected function transformData(array $payload): array
    {
        return [
            'enrollment_id' => $payload['enrollment_id'],
            'time' => $payload['time'],
            'state' => $payload['state'],
        ];
    }
}
