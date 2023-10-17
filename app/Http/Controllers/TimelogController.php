<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Enums\UserRole;
use App\Events\TimelogsProcessed;
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
        if ($request->expectsJson() && $request->raw) {
            return $employee
                ->timelogs()
                ->with('scanner')
                ->whereDate('time', $request->date)
                ->reorder()
                ->oldest('time')
                ->oldest('id')
                ->get()
                ->when(
                    $request->user()->role === UserRole::DEVELOPER,
                    fn ($e) => $e->makeVisible(['hidden', 'official'])
                );
        }

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
            'timelogs' => fn ($q) => $q->select(['timelogs.id', 'time', 'state', 'timelogs.scanner_id'])
                ->whereBetween('time', [$from, $to])
                ->with('scanner:scanners.id,name,print_background_colour,print_text_colour,priority'),
        ])->first();

        $service = app(TimelogService::class);

        return [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'timelogs' => $employee->timelogs->each
                ->setAppends([])
                ->makeHidden(['id', 'in', 'out', 'state', 'scanner_id', 'laravel_through_key'])
                ->groupBy(fn ($timelog) => $timelog->time->format('Y-m-d'))
                ->map(fn ($timelogs, $date) => $service->logsForTheDay($employee, Carbon::parse($date)))
                ->map(fn ($timelogs) => collect($timelogs)
                    ->filter(fn ($t, $k) => in_array($k, ['in1', 'in2', 'out1', 'out2', 'ut']))
                    ->map(fn ($t, $k) => $t && $k !== 'ut' ? [
                        'time' => $t?->time->format('H:i:s'),
                        'scanner' => $t?->scanner->name,
                        'print_background_colour' => $t?->scanner->print_background_colour,
                        'print_text_colour' => $t?->scanner->print_text_colour,
                    ] : ($k === 'ut' ? $t : null))->toArray()
                )
                ->map(fn ($timelogs, $date) => [
                    'date' => $date,
                    'valid' => ! @$timelogs['ut']?->invalid,
                    'shortfall' => [
                        'late' => @$timelogs['ut']?->in1 + @$timelogs['ut']?->in2,
                        'undertime' => @$timelogs['ut']?->out1 + @$timelogs['ut']?->out2,
                        'total' => @$timelogs['ut']?->total,
                    ],
                    'am' => ['in' => $timelogs['in1'], 'out' => $timelogs['out1']],
                    'pm' => ['in' => $timelogs['in2'], 'out' => $timelogs['out2']],
                ])
                ->sortKeys()
                ->values(),
        ];
    }

    public function store(StoreRequest $request, Import $import)
    {
        if ($request->has('file')) {
            $data = $import->parse($request->file);

            TimelogsProcessed::dispatch($request->user(), $data->toArray(), $request->scanner, $request->file('file')->getClientOriginalName());

            return redirect()->back();
        }

        Timelog::make()->forceFill($request->validated())->unofficialize()->save();

        return redirect()->back();
    }

    public function update(Request $request, Timelog $timelog)
    {
        $validated = $request->validate(['hidden' => 'required|boolean']);

        $timelog->update($validated);

        return redirect()->back();
    }

    public function destroy(Timelog $timelog)
    {
        $timelog->delete();

        return redirect()->back();
    }
}
