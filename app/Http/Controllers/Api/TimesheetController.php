<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimesheetRequest;
use App\Http\Resources\EmployeeTimesheetResourceCollection;
use App\Models\Employee;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TimesheetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(TimesheetRequest $request)
    {
        JsonResource::withoutWrapping();

        $validated = $request->safe();

        $month = Carbon::parse($validated['month']);

        $employees = Employee::query()
            ->{is_array($validated['uid']) ? 'whereIn' : 'where'}('uid', $validated['uid'])
            ->with([
                'timesheets' => fn ($query) => $query->select(['id', 'month', 'employee_id', 'timesheet_id'])
                    ->whereMonth('month', $month->month)->whereYear('month', $month->year),
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
            ]);

        return EmployeeTimesheetResourceCollection::make($employees->get());
    }
}
