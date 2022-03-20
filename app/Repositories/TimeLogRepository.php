<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Carbon\Carbon;

class TimeLogRepository extends BaseRepository
{
    public function transformImportData(array $row): array
    {
        return $this->transformData([
            'biometrics_id' => trim($row[0]),
            'time' => Carbon::createFromTimeString($row[1]),
            'state' => join('', collect(array_slice($row, 2))->map(fn ($row) => $row > 1 ? 1 : $row)->toArray()),
            'user_id' => request()->user()->id,
        ]);
    }

    protected function transformData(array $payload): array
    {
        return [
            'biometrics_id' => trim($payload['biometrics_id']),
            'time' => $payload['time'],
            'state' => $payload['state'],
            'user_id' => $payload['user_id'],
        ];
    }
}
