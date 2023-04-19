<?php

namespace App\Services;

use App\Http\Requests\PrintRequest;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrintService
{
    public function __construct(
        private PrintRequest $request,
        private EmployeeRepository $employee,
        private ScannerRepository $scanner,
    ) {
    }

    public function data(string $by): array
    {
        return $this->{$by}();
    }

    public function dtr()
    {
        return [
            ...$this->employee(),
            'csc_format' => $this->request->csc_format,
            'transmittal' => $this->request->transmittal,
        ];
    }

    public function employee(): array
    {
        return [
            ...$this->range(),
            'employees' => $this->employees(),
            'transmittal' => $this->request->transmittal,
        ];
    }

    public function group(): array
    {
        return $this->office();
    }

    public function office(): array
    {
        return [
            'offices' => $this->offices(),
            'scanners' => $this->scanners(),
            'dates' => collect($this->request->dates)->sort()->map(fn ($date) => Carbon::create($date)),
            'transmittal' => $this->request->transmittal,
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
            ->when(
                collect($this->request->offices)->isNotEmpty(),
                function ($collection) {
                    return $collection->groupBy(['office', 'regular']);
                },
                function ($collection) {
                    return $collection->flatMap->groups->unique()->mapWithKeys(fn ($group) => [$group => $collection->filter(fn ($employee) => in_array($group, $employee->groups))->groupBy('regular')]);
                }
            );

        return collect(collect($this->request->offices)->isNotEmpty() ? $this->request->offices : $this->request->groups)
            ->when(
                collect($this->request->offices)->isNotEmpty(),
                function ($collection) {
                    return $collection->mapWithKeys(fn ($o) => [$o => ['scanners' => $this->scanner->query()->whereHas('employees', fn ($q) => $q->where('office', $o))->when($this->request->has('scanners'), fn ($q) => $q->whereIn('id', $this->request->scanners))->get()]]);
                },
                function ($collection) {
                    return $collection->mapWithKeys(
                        fn ($o) => [
                            $o => [
                                'scanners' => $this->scanner->query()->whereHas(
                                    'employees',
                                    function ($query) {
                                        $query->where(fn ($q) => collect($this->request->groups)->each(fn ($e) => $q->orWhereJsonContains('groups', $e)));
                                    },
                                )->when(
                                    $this->request->filled('scanners'),
                                    fn ($q) => $q->whereIn('id', $this->request->scanners),
                                )->get(),
                            ],
                        ]
                    );
                }
            )->map(function ($office, $key) use ($offices) {
                return collect($office)->mergeRecursive($offices->first(fn ($d, $o) => $o === $key));
            });
    }

    public function scanners(): Collection
    {
        return $this->scanner->query()
            ->whereHas('employees', function ($query) {
                $query->when($this->request->filled('offices'), function ($query) {
                    $query->whereIn('office', $this->request->offices);
                }, function ($query) {
                    collect($this->request->groups)->each(fn ($group) => $query->orWhereJsonContains('groups', $group));
                });
            })
            ->when($this->request->has('scanners'), fn ($q) => $q->whereIn('id', $this->request->scanners))
            ->get();
    }

    private function query(): Builder
    {
        $query = $this->employee->query()
            ->whereActive(true);

        switch ($this->request->by) {
            case 'group':
            case 'office':
                $query->when($this->request->filled('groups'), function ($query) {
                    $query->where(function ($query) {
                        collect($this->request->groups)->each(fn ($group) => $query->orWhereJsonContains('groups', $group));
                    });
                }, function ($query) {
                    $query->whereIn('office', $this->request->offices);
                });
                $query->whereHas('timelogs', function ($q) {
                    $q->whereIn(DB::raw('DATE(time)'), $this->request->dates);
                    $q->whereHas('scanner', function ($q) {
                        $q->when(
                            $this->request->has('scanners'),
                            fn ($q) => $q->whereIn('scanners.id', $this->request->scanners),
                            fn ($q) => $q->where('name', 'like', '%coliseum-%'),
                        );
                    });
                });
                $query->with([
                    'timelogs.scanner',
                    'timelogs' => function ($q) {
                        $q->whereIn(DB::raw('DATE(time)'), $this->request->dates);
                        $q->whereHas('scanner', function ($q) {
                            $q->when(
                                $this->request->has('scanners'),
                                fn ($q) => $q->whereIn('scanners.id', $this->request->scanners),
                                fn ($q) => $q->where('name', 'like', '%coliseum%'),
                            );
                        });
                    },
                ]);
                break;
            case 'dtr':
            case 'employee':
                $query->with([
                    'scanners' => fn ($q) => $q->when($this->request->has('scanners'), fn ($q) => $q->whereIn('scanners.id', $this->request->scanners)),
                    'timelogs' => function ($query) {
                        ['from' => $from, 'to' => $to] = $this->range();
                        $query->whereHas('scanner', fn ($q) => $q->when($this->request->has('scanners'), fn ($q) => $q->whereIn('scanners.id', $this->request->scanners)))
                            ->when(
                                $this->request->filled('days'),
                                function ($query) {
                                    $query->where(function ($query) {
                                        collect($this->request->days)->each(fn ($day) => $query->orWhereDay('time', $day));
                                    });
                                }
                            )->when(
                                @$this->request->weekends['excluded'] xor @$this->request->weekdays['excluded'],
                                function ($query) use ($from, $to) {
                                    $filter = function ($week) use ($query, $from, $to) {
                                        $query->where(function ($query) use ($from, $to, $week) {
                                            collect($from->toPeriod($to)->toArray())->reject->$week()->each(function ($date) use ($query) {
                                                $query->orWhereDate('time', $date);
                                            });
                                        });
                                    };

                                    if ($this->request->weekends['excluded']) {
                                        $filter('isWeekend');
                                    }
                                    if ($this->request->weekdays['excluded']) {
                                        $filter('isWeekday');
                                    }
                                },
                                function ($query) use ($from, $to) {
                                    $query->whereBetween('time', [$from->subDay(), $to->addDay()]);
                                }
                            );
                    },
                    'timelogs.scanner',
                ])
                ->when($this->request->filled('offices'), fn ($q) => $q->whereIn('office', $this->request->offices), fn ($q) => $q->whereIn('id', $this->request->employees))
                ->when($this->request->filled('regular'), fn ($q) => $q->whereRegular((bool) $this->request->regular));
                break;
        }

        return $query;
    }

    private function range(): array
    {
        return match ($this->request->period) {
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
