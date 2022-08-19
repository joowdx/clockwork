<?php

namespace App\Http\Controllers;

use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use App\Services\OfficeService;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceController extends Component
{
    use WithPagination;

    protected $queryString = [
        'search',
        'from',
        'start',
        'end',
        'active',
        'office',
        'status',
    ];

    public $url;

    public $search;

    public $from = 'offices';

    public $active = '';

    public $start;

    public $end;

    public $selected;

    public $office;

    public $status = '';

    public function mount()
    {
        $this->selected['scanners'] = $this->selected['scanners'] = app(ScannerRepository::class)->all()
            ->when($this->from === 'offices', fn($scanners) => $scanners->filter(fn ($scanner) => str_contains(strtolower($scanner->name), 'coliseum')))
            ->mapWithKeys(fn ($scanner) => [$scanner->id => true])
            ->toArray();

        $this->start = today()->startOfWeek()->format('Y-m-d');

        $this->end = today()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedFrom()
    {
        $this->office = $this->from !== 'offices'
            ? $this->office
            : '';

        $this->search = '';

        $this->selected['employees'] = [];

        $this->selected['offices'] = [];

        $this->selected['scanners'] = app(ScannerRepository::class)->all()
            ->when($this->from === 'offices', fn($scanners) => $scanners->filter(fn ($scanner) => str_contains(strtolower($scanner->name), 'coliseum')))
            ->mapWithKeys(fn ($scanner) => [$scanner->id => true])
            ->toArray();

        $this->resetValidation();

        $this->resetPage();
    }

    public function updatedOffice()
    {
        $this->resetPage();

        $this->selected['employees'] = [];
    }

    public function updatedStatus()
    {
        $this->resetPage();

        $this->selected['employees'] = [];
    }

    public function updatedActive()
    {
        $this->resetPage();

        $this->selected['employees'] = [];

        $this->selected['offices'] = [];
    }

    public function render()
    {
        $scanner = app(ScannerRepository::class);

        $employee = app(EmployeeRepository::class);

        $office = app(OfficeService::class);

        return view('attendance', [
            'scanners' => $scanner->query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->get(),
            'employees' => $this->from === 'employees'
                ? $employee->query()
                    ->when($this->active !== '', fn ($query) => $query->whereActive(filter_var($this->active, FILTER_VALIDATE_BOOLEAN)))
                    ->when($this->status !== '', fn ($query) => $query->whereRegular(filter_var($this->status, FILTER_VALIDATE_BOOLEAN)))
                    ->when($this->office, fn ($query) => $query->whereOffice(strtoupper($this->office)))
                    ->simplePaginate(30)
                : [],
            'offices' => $office->query()->where('name', 'like', "%{$this->search}%")->pluck('name')->prepend(''),
        ]);
    }

    protected function messages()
    {
        return [
            'selected.employees.required_if' => 'Please select an employee.',
            'selected.offices.required_if' => 'Please select an office.',
            'selected.scanners.required' => 'Please select a scanner.',
        ];
    }

    protected function rules()
    {
        return [
            'selected.employees' => 'required_if:from,employees|array',
            'selected.employees.*' => 'uuid|exists:employees,id',
            'selected.offices' => 'required_if:from,offices|array',
            'selected.offices.*' => 'string|exists:employees,office',
            'selected.scanners' => 'required|array',
            'selected.scanners.*' => 'uuid|exists:scanners,id',
            'start' => 'required|date:Y-m-d|before_or_equal:' . ($this->from === 'offices' ? today()->endOfDay()->format('Y-m-d') :  'end'),
            'end' => 'required_if:from,employees|date:Y-m-d|before_or_equal:' . today()->endOfMonth()->format('Y-m-d'),
        ];
    }

    protected function prepareForValidation($attributes)
    {
        $attributes['selected'] = collect($attributes['selected'])
            ->map(
                fn ($att) => collect($att)->filter()->keys()->toArray()
            )
            ->toArray();

        return $attributes;
    }

    public function generate()
    {
        if ($this->url) {
            $this->url = null;

            return;
        }

        $this->validate();

        $this->url = match ($this->from) {
            'employees' => route('print', [
                'by' => 'employee',
                'period' => 'custom',
                'from' => $this->start,
                'to' => $this->end,
                'employees' => collect(@$this->selected['employees'])
                    ->filter()
                    ->keys()
                    ->values()
                    ->toArray(),
                'scanners' => collect(@$this->selected['scanners'])
                    ->filter()
                    ->keys()
                    ->values()
                    ->toArray(),
            ]),
            'offices' => route('print', [
                'by' => 'office',
                'date' => $this->start,
                'offices' => collect(@$this->selected['offices'])
                    ->filter()
                    ->keys()
                    ->map(fn ($office) => strtoupper($office))
                    ->values()
                    ->toArray(),
                'scanners' => collect(@$this->selected['scanners'])
                    ->filter()
                    ->keys()
                    ->values()
                    ->toArray(),
            ]),
        };

    }
}
