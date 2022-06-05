<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Carbon\Carbon;

class TimeLogRepository extends BaseRepository
{
    protected function transformData(array $payload): array
    {
        return [
            'employee_scanner_id' => $payload['employee_scanner_id'],
            'time' => $payload['time'],
            'state' => $payload['state'],
        ];
    }
}
