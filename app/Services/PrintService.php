<?php

namespace App\Services;

use App\Http\Requests\PrintRequest;
use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PrintService
{
    public function __construct(
        private PrintRequest $request,
        private EmployeeRepository $employee,
        private ScannerRepository $scanner,
    ) { }

    public function view(): string
    {
        return "print.{$this->request->view}";
    }

    public function data(): array
    {
        return $this->{$this->request->view}();
    }

    public function employee(): array
    {
        return [
            ...$this->range(),
            'employees' => $this->employees(),
        ];
    }

    public function office(): array
    {
        return [
            'offices' => $this->offices(),
            'scanners' => $this->scanners(),
            'date' => Carbon::create($this->request->date),
        ];
    }

    public function employees(): Collection
    {
        return $this->query()->get();
    }

    public function offices(): Collection
    {
        $offices = $this->query()->get()
            ->map(function ($employee) {
                $employee->regular = $employee->regular ? 'regular' : 'nonregular';
                return $employee;
            })
            ->groupBy(['office', 'regular']);

        if ($offices->isEmpty()) {
            return collect($this->request->offices)->mapWithKeys(fn ($o) => [$o => []]);
        }

        return $offices;
    }

    public function scanners(): Collection
    {
        return $this->scanner->query()
            ->whereHas('employees', fn ($q) => $q->whereIn('office', $this->request->offices))
            ->get();
    }

    private function query(): Employee|Builder
    {
        $query = $this->employee->query();

        switch ($this->request->view) {
            case 'office': {
                $query->with(['timelogs.scanner', 'timelogs' => fn ($q) => $q->whereDate('time', $this->request->date)]);
                $query->whereHas('timelogs', fn ($q) => $q->whereDate('time', $this->request->date));
                $query->whereIn('office', $this->request->offices);
                break;
            };
            case 'employee': {
                $query->with(['timelogs.scanner', 'timelogs' => fn ($q) => $q->whereBetween('time', $this->range())]);
                $query->whereIn('id', $this->request->employees);
                break;
            };
        }

        return $query;
    }

    private function range(): array
    {
        switch ($this->request->period) {
            case 'full':
            case '1st':
            case '2nd': {
                $month = Carbon::parse($this->request->month);

                return [
                    'from' => $month->setDay($this->request->period == '2nd' ? 16 : 1),
                    'to' => $this->request->period == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth(),
                ];
            };
            case 'custom': {
                return [
                    'from' => Carbon::parse($this->request->from)->startOfDay(),
                    'to' => Carbon::parse($this->request->to)->endOfDay(),
                ];
            };
            default: {
                return [];
            }
        }
    }
}
