<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class PrintPreviewController extends Controller
{
    /**
     * Provision a new web server.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'month' => ['required', 'date:Y-m'],
            'period' => ['required', 'in:full,1st,2nd'],
            'id' => ['nullable', 'array'],
            'id.*' => ['numeric', 'exists:employees,id']
        ]);

        $month = $request->month ? Carbon::parse($request->month) : today()->startOfMonth();

        $start = $month->setDay($request->period == '2nd' ? 16 : 1);

        $end = $request->period == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth();

        $request->id ? $request->user()->load([
            'employees' => fn ($q) => $q->whereIn('id', $request->id),
            'employees.logs' => fn($q) => $q->whereBetween('time', [$start, $end])
        ]) : [];

        return view('dtr', [
            'from' => $start,
            'to' => $end,
            'employees' => $request->user()->employees,
        ]);
    }
}
