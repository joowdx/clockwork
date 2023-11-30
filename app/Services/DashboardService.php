<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function getTardiesAndUndertimes(string $month)
    {
        [$year, $month] = explode('-', $month);

        $employees = Employee::active()->select(['name', 'office', 'id'])->with([
            'timelogs' => fn ($t) => $t->whereMonth('time', $month)->whereYear('time', $year)
        ])->whereHas('timelogs', fn ($t) => $t->whereMonth('time', $month)->whereYear('time', $year))
            ->whereOffice('pgo-picto')
            ->sortByName()
        ->get();

        $offices = $employees->groupBy('office');

        $service = app(TimelogService::class);

        return $offices->map(function ($office) use ($service) {
            $employees = $office->map(function ($employee) use ($service) {

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
                'employees' => $filtered->count(),
                'excluded' => $office->count() - $filtered->count(),
                'tardy' => [
                    'count' => $filtered->sum('tardy'),
                    'max' => $filtered->max('tardy'),
                    'min' => $filtered->min('tardy'),
                    'mean' => $filtered->average('tardy'),
                    'median' => $filtered->median('tardy'),
                    'mode' => $filtered->mode('tardy'),
                ],
                'undertime' => [
                    'count' => $filtered->sum('undertime'),
                    'max' => $filtered->max('undertime'),
                    'min' => $filtered->min('undertime'),
                    'mean' => $filtered->average('undertime'),
                    'median' => $filtered->median('undertime'),
                    'mode' => $filtered->mode('undertime'),
                ],
            ];
        });
    }
}
