<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Laravel\Octane\Facades\Octane;

class DashboardService
{
    public function getTardiesAndUndertimes(string $month)
    {
        [$year, $month] = explode('-', $month);

        $offices = Employee::select('office')->distinct('office')->pluck('office')->filter();

        return Octane::concurrently(
            $offices->mapWithKeys(function ($office) use ($year, $month) {
                return [
                    $office =>
                    function () use ($office, $year, $month) {
                        $service = app(TimelogService::class);

                        $employees = Employee::active()
                            ->whereOffice($office)
                            ->select(['office', 'id'])
                            ->with(['timelogs' => fn ($t) => $t->whereMonth('time', $month)->whereYear('time', $year)])
                            ->whereHas('timelogs', fn ($t) => $t->whereMonth('time', $month)->whereYear('time', $year))
                            ->sortByName()
                            ->get();

                        $employees = $employees->map(function ($employee) use ($service) {
                            return $employee->timelogs->groupBy(fn ($t) => $t->time->format('Y-m-d'))
                                ->map(fn ($timelogs, $date) => $service->logsForTheDay($employee, Carbon::parse($date)))
                                ->reduce(function ($carry, $item) {
                                    $carry['tardy'] += (@$item['ut']?->in1 + @$item['ut']?->in2 > 0 ? 1 : 0);
                                    $carry['undertime'] += (@$item['ut']?->out1 + @$item['ut']?->out2 > 0 ? 1 : 0);
                                    $carry['invalid'] += @$item['ut']?->invalid ? 1 : 0;
                                    $carry['count'] += 1;

                                    return $carry;
                                }, [
                                    'tardy' => 0,
                                    'undertime' => 0,
                                    'invalid' => 0,
                                    'count' => 0,
                                ]);
                        });

                        $filtered = $employees->filter(fn ($e) => $e['count'] > 5 && $e['invalid'] / $e['count'] < 0.8);

                        return [
                            'office' => $office,
                            'employees' => $filtered->count() ?? 0,
                            'excluded' => $employees->count() - $filtered->count(),
                            'tardy' => [
                                'count' => $filtered->sum('tardy') ?? 0,
                                'max' => $filtered->max('tardy') ?? 0,
                                'min' => $filtered->min('tardy') ?? 0,
                                'mean' => $filtered->average('tardy') ?? 0,
                                'median' => $filtered->median('tardy') ?? 0,
                                'mode' => $filtered->mode('tardy') ?? 0,
                            ],
                            'undertime' => [
                                'count' => $filtered->sum('undertime') ?? 0,
                                'max' => $filtered->max('undertime') ?? 0,
                                'min' => $filtered->min('undertime') ?? 0,
                                'mean' => $filtered->average('undertime') ?? 0,
                                'median' => $filtered->median('undertime') ?? 0,
                                'mode' => $filtered->mode('undertime') ?? 0,
                            ],
                        ];
                    }
                ];
            })->toArray(),
            10000
        );
    }
}
