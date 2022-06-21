<?php

namespace App\Actions\FileImport;

use App\Models\Enrollment;

class InsertEnrollments
{
    public function __invoke(array $payload): void
    {
        Enrollment::upsert($payload, ['scanner_id', 'uid'], ['employee_id']);
    }
}
