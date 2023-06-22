<?php

namespace App\Services;

use App\Http\Requests\PrintRequest;
use App\Models\Employee;
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
        $employee->query()->whereActive(true);
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

    public function search(): array
    {
        return [
            ...$this->range(),
            'employees' => $this->query()->get(),
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

    private function query(?string $query = null): Builder
    {
        $query = $this->employee->query();

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
                $this->timelogs($query)->when($this->request->filled('offices'), fn ($q) => $q->whereIn('office', $this->request->offices), fn ($q) => $q->whereIn('id', $this->request->employees))
                ->when($this->request->filled('regular'), fn ($q) => $q->whereRegular((bool) $this->request->regular));
                break;
            case 'search':
                if (isset($this->request->name['middle']) && isset($this->request->name['extension'])) {
                    if (($employee = $this->find($this->request->name))?->exists) {
                        $this->timelogs($query)->whereId($employee->id);
                    } elseif (($employee = $this->find([...$this->request->name, 'extension' => null]))?->exists) {
                        $this->timelogs($query)->whereId($employee->id);
                    } elseif (($employee = $this->find([...$this->request->name, 'middle' => null]))?->exits) {
                        $this->timelogs($query)->whereId($employee->id);
                    } else {
                        abort(404);
                    }
                }

                $employee = $this->find($this->request->name);

                if (isset($this->request->name['middle']) && ! $employee?->exists) {
                    $employee = $this->find([...$this->request->name, 'middle' => null]);
                }

                if (isset($this->request->name['extension']) && ! $employee?->exists) {
                    $employee = $this->find([...$this->request->name, 'extension' => null]);
                }

                abort_unless($employee?->exists, 404);

                $this->timelogs($query)->whereId($employee->id);

                break;
        }

        return $query;
    }

    private function find($name)
    {
        $query = Employee::query();

        collect($name)->filter()->each(fn ($name, $field) => $query->where("name->$field", strtoupper($name)));

        return $query->first(['id']);
    }

    private function timelogs(Builder &$query): Builder
    {
        return $query->with([
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
        ]);
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
