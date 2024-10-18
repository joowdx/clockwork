<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TimesheetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $date = function ($a, $v, $f) use ($request) {
            if (empty($request->input('month'))) {
                return;
            }

            $month = DateTime::createFromFormat('Y-m', $request->month);

            if ($month && $month->format('Y-m') !== $request->month) {
                return;
            }

            if (($date = DateTime::createFromFormat('Y-m-d', $v)) && $date->format('Y-m-d') !== $v) {
                return $f('The selected date is invalid.');
            }

            if (Carbon::parse($month)->format('Y-m') !== Carbon::parse($v)->format('Y-m')) {
                return $f('The selected date is invalid.');
            }
        };

        $validator = Validator::make($request->all(), [
            'from' => ['required_if:period,range', 'date_format:Y-m-d', 'before_or_equal:to', $date],
            'to' => ['required_if:period,range', 'date_format:Y-m-d', 'after_or_equal:from', $date],
            'dates.*' => ['required', $date],
            'dates' => 'required_if:period,dates|array',
            'period' => 'required|string|in:1st,2nd,full,regular,overtime,dates,range',
            'month' => 'required|string|date_format:Y-m',
            'uid.*' => 'required|string|exists:employees,uid',
            'uid' => ['required', function ($a, $v, $f) {
                if (! is_string($v) && ! is_array($v)) {
                    $f('The uid field is invalid.');
                }

                if (is_string($v) && Employee::where('uid', $v)->doesntExist()) {
                    $f('The selected uid is invalid.');
                }
            }],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $validated = $validator->validated();

        $month = Carbon::parse($validated['month']);

        $hidden = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'email_verified_at',
            'laravel_through_key',
            'employee_id',
            'timesheet_id',
            'rectified',
            'digest',
            'birthdate',
            'sex',
            'office_id',
        ];

        $employees = Employee::query()
            ->{is_array($validated['uid']) ? 'whereIn' : 'where'}('uid', $validated['uid'])
            ->with([
                'timesheets' => fn ($query) => $query->select(['id', 'month', 'employee_id'])->whereMonth('month', $month->month)->whereYear('month', $month->year),
                'timesheets.timetables' => function ($query) use ($validated) {
                    match ($validated['period']) {
                        '1st' => $query->firstHalf(),
                        '2nd' => $query->secondHalf(),
                        'regular' => $query->regularDays(),
                        'overtime' => $query->overtimeWork(),
                        'dates' => $query->whereIn('date', $validated['dates']),
                        'range' => $query->whereBetween('date', [$validated['from'], $validated['to']]),
                        default => $query,
                    };
                },
            ])
            ->lazyById()
            ->mapWithKeys(function ($employee) use ($hidden) {
                $timesheet = $employee->timesheets->first();

                unset($employee->timesheets);

                $timetables = $timesheet?->timetables;

                unset($timesheet->timetables);

                $timesheet->timetables = $timetables?->mapWithKeys(function ($timetable) use ($hidden) {
                    $timetable->punch = empty($timetable->punch) ? null : collect($timetable->punch)->map(function ($punch) {
                        return ($punch['missed'] ?? false) ? null : [
                            'time' => preg_replace('/:\d+/', '', $punch['time'], 1),
                            'undertime' => $punch['undertime'],
                        ];
                    });

                    return [$timetable->date->format('Y-m-d') => $timetable->makeHidden($hidden)];
                });

                $employee->timesheet = $timesheet->makeHidden($hidden);

                return [$employee->uid => $employee->makeHidden($hidden)->append('titled_name')->toArray()];
            })
            ->toArray();

        return response()->json(is_array($validated['uid']) ? $employees : current($employees));
    }
}
