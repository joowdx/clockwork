<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Enums\UserRole;
use App\Http\Requests\TimeLog\StoreRequest;
use App\Models\Employee;
use App\Models\Timelog;
use App\Services\TimelogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimelogController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        if ($request->expectsJson() && $request->user()?->type === UserRole::DEPARTMENT_HEAD) {
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
                'timelogs' => fn ($q) => $q->whereBetween('time', [$from->subDay(), $to->addDay()]),
            ])->first();

            $service = app(TimelogService::class);

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
                $request->user()->type === UserRole::DEVELOPER,
                fn ($e) => $e->makeVisible('hidden')
            );
    }

    public function store(StoreRequest $request, Import $import)
    {
        if ($request->has('file')) {
            $import->parse($request->file);

            return redirect()->back();
        }

        Timelog::make()->forceFill($request->validated())->save();
    }

    public function update(Request $request, Timelog $timelog)
    {
        $validated = $request->validate(['hidden' => 'required|boolean']);

        $timelog->update($validated);
    }

    public function destroy(Timelog $timelog)
    {
        $timelog->delete();
    }
}
