<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Enums\UserType;
use App\Http\Requests\TimeLog\StoreRequest;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeLogController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        if ($request->expectsJson() && $request->user()?->type === UserType::DEPARTMENT_HEAD) {
            @['from' => $from, 'to' => $to] = match ($request->period) {
                'full', '1st', '2nd' => [
                    'from' => ($month = Carbon::parse($request->month))->setDay($request->period == '2nd' ? 16 : 1),
                    'to' => $request->period == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth(),
                ],
                'custom' => [
                    'from' => Carbon::parse($request->from)->startOfDay(),
                    'to' => Carbon::parse($request->to)->endOfDay(),
                ],
                null => [
                    'from' => Carbon::parse($request->month)->startOfMonth(),
                    'to' => Carbon::parse($request->month)->endOfMonth(),
                ],
                default => [],
            };

            $employee = Employee::whereId($employee->id)->with([
                'timelogs' => fn ($q) => $q->whereBetween('time', [$from->subDay(), $to->addDay()])
            ])->first();

            $service = app(TimeLogService::class);

            $employee->timelog = $employee->timelogs
                ->groupBy(fn ($timelog) => $timelog->time->format('Y-m-d'))
                ->map(fn ($timelogs, $date) => $service->logsForTheDay($employee, Carbon::parse($date)));

            return $employee;
        }

        return $employee
            ->timelogs()
            ->with('scanner')
            ->whereDate('time', $request->date)
            ->reorder()
            ->oldest('time')
            ->oldest('id')
            ->get()
            ->when(
                $request->user()->type === UserType::DEVELOPER,
                fn ($e) => $e->makeVisible('hidden')
            );
    }

    public function store(StoreRequest $request, Import $import)
    {
        if ($request->has('file')) {
            $import->parse($request->file);

            return redirect()->back();
        }

        TimeLog::make()->forceFill($request->validated())->save();
    }

    public function update(Request $request, TimeLog $timelog)
    {
        $validated = $request->validate(['hidden' => 'required|boolean']);

        $timelog->update($validated);
    }

    public function destroy(TimeLog $timelog)
    {
        $timelog->delete();
    }
}
