<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Scanner;

class EnrollmentService
{
    public function sync(Employee|Scanner $model, array $payload): void
    {
        switch (get_class($model)) {
            case Employee::class:
                $model->scanners()->sync($payload);
                break;

            case Scanner::class:
                $model->employees()->sync($payload);
                break;
        }
    }

    public function destroy(Enrollment $enrollment): void
    {
        $enrollment->delete();
    }
}
