<?php

namespace App\Http\Controllers\Print;

use App\Http\Requests\PrintRequest;
use App\Services\TimelogService;
use Carbon\CarbonPeriod;
use Livewire\Component;

class Dtr extends Component
{
    public $employee;

    public $from;

    public $to;

    public function render(PrintRequest $request, TimelogService $service)
    {
        return view('print.dtr', [
            'user' => $request->user(),
            'time' => $service->time(),
            'months' => CarbonPeriod::create($this->from, $this->to)->setDateInterval('1 mo')->toArray(),
            'attlogs' => collect(CarbonPeriod::create($this->from, $this->to))->mapWithKeys(function ($date) use ($service) {
                return [$date->format('Y-m-d') => $service->logsForTheDay($this->employee, $date)];
            })->toArray(),
            'calculate' => @$request->calculate,
            'days' => $request->days,
            'week' => (@$request->weekdays['excluded'] xor @$request->weekends['excluded']) ? (@$request->weekdays['excluded'] ? 'weekends' : 'weekdays') : null,
        ]);
    }
}
