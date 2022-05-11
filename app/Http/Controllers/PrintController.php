<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrintRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Carbon\Carbon;

class PrintController extends Controller
{
    /**
     * Provision a new web server.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(PrintRequest $request)
    {
        $month = $request->month ? Carbon::parse($request->month) : today()->startOfMonth();

        $start = $month->setDay($request->period == '2nd' ? 16 : 1);

        $end = $request->period == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth();

        $employees = $request->id ? app(EmployeeService::class)->loadLogs($request->id, $start, $end) : [];

        return view('dtr', [
            'from' => $start,
            'to' => $end,
            'employees' => $employees,
        ]);
    }
}
