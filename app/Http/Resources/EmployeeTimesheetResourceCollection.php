<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeTimesheetResourceCollection extends ResourceCollection
{
    protected array $hidden = [
        'id',
        'employee_id',
        'timesheet_id',
        'office_id',
        'laravel_through_key',
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'rectified',
        'digest',
        'birthdate',
        'sex',
    ];

    public function toArray(Request $request): array
    {
        $format = function ($employee) {
            $employee->makeHidden($this->hidden)->append('titled_name');

            $timesheet = $employee->timesheets->first();

            unset($employee->timesheets);

            $timetables = $timesheet?->timetables;

            unset($timesheet->timetables);

            $timesheet->timetables = $timetables?->mapWithKeys(function ($timetable) {
                $timetable->punch = empty($timetable->punch) ? null : collect($timetable->punch)->map(function ($punch) {
                    return ($punch['missed'] ?? false) ? null : [
                        'time' => preg_replace('/:\d+/', '', $punch['time'], 1),
                        'undertime' => $punch['undertime'],
                    ];
                });

                return [$timetable->date->format('Y-m-d') => $timetable->makeHidden($this->hidden)];
            });

            $employee->timesheet = $timesheet->makeHidden($this->hidden);

            $employee->middle_name = $employee->middle_name !== 'N/A' ? $employee->middle_name : null;

            $employee->qualifier_name = $employee->qualifier_name !== 'N/A' ? $employee->qualifier_name : null;

            $array = $employee->toArray();

            return [
                $employee->uid => ['titled_name' => $employee->titled_name] + array_diff_key($array, ['titled_name' => '']),
            ];
        };

        $employees = $this->collection->mapWithKeys($format)->toArray();

        if ($request->has('uid') && is_string($request->uid)) {
            return reset($employees);
        }

        return $employees;
    }
}
