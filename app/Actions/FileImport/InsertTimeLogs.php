<?php

namespace App\Actions\FileImport;

use App\Repositories\TimeLogRepository;

class InsertTimeLogs
{
    public function __construct(
        private TimeLogRepository $timelog
    ) { }

    public function __invoke(array $payload)
    {
        $this->timelog->upsert($payload, ['enrollment_id', 'time', 'state']);
    }
}
