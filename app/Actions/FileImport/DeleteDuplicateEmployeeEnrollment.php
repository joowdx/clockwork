<?php

namespace App\Actions\FileImport;

use App\Models\Enrollment;

class DeleteDuplicateEmployeeEnrollment
{
    public function __invoke(): void
    {
        $dupe = Enrollment::groupBy('employee_id', 'scanner_id')->havingRaw('count(uid) > 1');

        $duplicates = Enrollment::from('enrollments as a')
            ->joinSub($dupe->clone()->select('employee_id', 'scanner_id'), 'b', fn ($join) => $join->on('a.scanner_id', 'b.scanner_id')->on('a.employee_id', 'b.employee_id'))
            ->whereNotIn('a.id', $dupe->clone()->selectRaw('max(id) as id'))
            ->pluck('id');

        Enrollment::destroy($duplicates);
    }
}
