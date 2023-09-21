<?php

namespace App\Actions\FileImport;

use App\Repositories\TimelogRepository;

class InsertTimelogs
{
    public function __construct(
        private TimelogRepository $timelog
    ) {
    }

    public function __invoke(array $payload)
    {
        $this->timelog->upsert($payload, ['uid', 'scanner_id', 'time', 'state']);
    }
}
