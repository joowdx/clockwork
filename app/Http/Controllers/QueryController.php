<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuerySearchRequest;
use App\Models\Employee;
use App\Services\TimelogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class QueryController extends Controller
{
    public function search(QuerySearchRequest $request)
    {
        if ($request->isMethod('GET')) {
            $employee = Employee::find($request->employee);

            return inertia('Query/Search', match (empty($employee)) {
                true => [],
                default => [
                    'employee' => $employee->id,
                    'name' => $employee->name_format->shortStartLast,
                    'proceed' => ! empty($employee->pin),
                ]
            });
        }

        $employee = Employee::query()
            ->where('name->first', $request->name['first'])
            ->where('name->last', $request->name['last'])
            ->when(
                isset($request->name['middle']),
                fn ($q) => $q->where('name->middle', $request->name['middle']),
                fn ($q) => $q->where(fn ($q) => $q->whereNull('name->middle')->orWhere('name->middle', '')),
            )
            ->when(
                isset($request->name['extension']),
                fn ($q) => $q->where('name->extension', $request->name['extension']),
                fn ($q) => $q->where(fn ($q) => $q->whereNull('name->extension')->orWhere('name->extension', '')),
            )
            ->first();

        throw_if(empty($employee), ValidationException::withMessages(['employee' => 'Employee not found.']));

        $scanners = $employee->scanners()
            ->select(['scanners.id', 'name'])
            ->reorder()
            ->orderBy('priority', 'desc')
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->makeHidden(['pivot']);

        if (! $request->expectsJson()) {
            return redirect()->route('query.search', ['employee' => $employee->id]);
        }

        return [
            'employee' => $employee,
            'scanners' => $scanners,
            'action' => $employee->pin ? 'proceed' : 'setup',
        ];
    }

    public function result(Request $request, Employee $employee)
    {
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

        $data = [
            'update' => [
                'scanners' => $recent = $employee->scanners->mapWithKeys(fn ($s) => [$s->name => $s->lastUpload?->time]),
                'recent' => $recent->filter()->max(),
            ],
            'employee' => $employee,
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

        if ($request->expectsJson() && ! $request->inertia()) {
            return $data;
        }

        return inertia('Query/Result', $data);
    }
}
