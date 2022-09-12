<?php

namespace App\Services;

use App\Http\Requests\PrintRequest;
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

    public function data(string $by): array
    {
        return $this->{$by}();
    }

    public function dtr()
    {
        return [...$this->employee(), 'month' => Carbon::parse($this->request->month)];
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

        return collect($this->request->offices)
            ->mapWithKeys(fn ($o) => [$o => ['scanners' => $this->scanner->query()->whereHas('employees', fn ($q) => $q->where('office', $o))->when($this->request->has('scanners'), fn ($q) => $q->whereIn('id', $this->request->scanners))->get()]])
            ->map(function ($office, $key) use ($offices) {
                return collect($office)->mergeRecursive($offices->first(fn ($d, $o) => $o === $key));
            });
    }

    public function scanners(): Collection
    {
        return $this->scanner->query()
            ->whereHas('employees', fn ($q) => $q->whereIn('office', $this->request->offices))
            ->when($this->request->has('scanners'), fn ($q) => $q->whereIn('id', $this->request->scanners))
            ->get();
    }

    private function query(): Builder
    {
        $query = $this->employee->query();

        switch ($this->request->by) {
            case 'office': {
                $query->whereIn('office', $this->request->offices);
                $query->whereHas('timelogs', function ($q) {
                    $q->whereDate('time', $this->request->date);
                    $q->whereHas('scanner', function ($q) {
                        $q->when(
                            $this->request->has('scanners'),
                            fn ($q) => $q->whereIn('scanners.id', $this->request->scanners),
                            fn ($q) => $q->where('name', 'like', '%coliseum%'),
                        );
                    });
                });
                $query->with([
                    'timelogs.scanner',
                    'timelogs' => function ($q) {
                        $q->whereDate('time', $this->request->date);
                        $q->whereHas('scanner', function ($q) {
                            $q->when(
                                $this->request->has('scanners'),
                                fn ($q) => $q->whereIn('scanners.id', $this->request->scanners),
                                fn ($q) => $q->where('name', 'like', '%coliseum%'),
                            );
                        });
                    }
                ]);
                break;
            };
            case 'dtr':
            case 'employee': {
                $query->with([
                    'scanners' => fn ($q) => $q->when($this->request->has('scanners'), fn ($q) => $q->whereIn('scanners.id', $this->request->scanners)),
                    'timelogs' => function ($query) {
                        $query->whereHas(
                            'scanner', fn ($q) => $q->when($this->request->has('scanners'), fn ($q) => $q->whereIn('scanners.id', $this->request->scanners))
                        )->whereBetween('time', $this->range());
                    },
                    'timelogs.scanner',
                ]);
                $query->when($this->request->filled('offices'), fn ($q) => $q->whereIn('office', $this->request->offices), fn ($q) => $q->whereIn('id', $this->request->employees));
                $query->when($this->request->filled('regular'), fn ($q) => $q->whereRegular((bool) $this->request->regular));
                break;
            };
        }

        return $query;
    }

    private function range(): array
    {
        return match($this->request->period) {
            'full', '1st', '2nd' => [
                'from' => ($month = Carbon::parse($this->request->month))->setDay($this->request->period == '2nd' ? 16 : 1),
                'to' => $this->request->period == '1st' ? $month->clone()->setDay(15)->endOfDay() : $month->clone()->endOfMonth(),
            ],
            'custom' => [
                'from' => Carbon::parse($this->request->from)->startOfDay(),
                'to' => Carbon::parse($this->request->to)->endOfDay(),
            ],
            null => [
                'from' => Carbon::parse($this->request->month)->startOfMonth(),
                'to' => Carbon::parse($this->request->month)->endOfMonth(),
            ],
            default => [],
        };
    }
}
