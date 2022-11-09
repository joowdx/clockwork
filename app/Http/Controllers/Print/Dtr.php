<?php

namespace App\Http\Controllers\Print;

use App\Services\TimeLogService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Livewire\Component;

class Dtr extends Component
{
    public $employee, $from, $to;

    protected $timelogs;

    public function mount()
    {
        $this->timelogs = app(TimeLogService::class);
    }

    public function render(Request $request, TimeLogService $service)
    {
        return view('print.dtr', [
            'time' => $service->time(),
            'months' => CarbonPeriod::create($this->from, $this->to)->setDateInterval('1 mo')->toArray(),
            'attlogs' => collect(CarbonPeriod::create($this->from, $this->to))->mapWithKeys(function ($date) use ($service) {
                return [$date->format('Y-m-d') => $service->logsForTheDay($this->employee, $date)];
            })->toArray(),
            'calculate' => @$request->calculate,
            'dates' => $request->dates,
            'week' => $request->weekdays['excluded'] xor $request->weekends['excluded'] ? ($request->weekdays['excluded'] ? 'weekends' : 'weekdays') : null,
        ]);
    }
}
