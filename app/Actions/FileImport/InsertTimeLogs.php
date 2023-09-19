<?php

namespace App\Actions\FileImport;

use App\Repositories\TimelogRepository;

class InsertTimeLogs
{
    public function __construct(
        private TimelogRepository $timelog
    ) {
    }

    public function __invoke(array $payload)
    {
        $this->timelog->upsert($payload, ['scanner_id', 'time', 'state']);
    }
}
