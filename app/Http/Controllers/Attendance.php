<?php

namespace App\Http\Controllers;

use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use Livewire\Component;
use Livewire\WithPagination;

class Attendance extends Component
{
    use WithPagination;

    protected $queryString = ['search', 'from'];

    protected $employee, $scanner;

    public $search;

    public $from = 'office';

    public $active = true;

    public $selected;

    public $office;

    public function updatedFrom()
    {
        $this->office = $this->from !== 'offices'
            ? $this->office
            : '';
    }

    public function boot()
    {
        $this->employee = app(EmployeeRepository::class);

        $this->scanner = app(ScannerRepository::class);
    }

    public function render()
    {
        return view('attendance', [
            'scanners' => $this->scanner->query()->get(),
            'employees' => $this->from === 'employees'
                ? $this->employee->query()
                    ->when(is_bool($this->active), fn ($query) => $query->whereActive($this->active))
                    ->when($this->office, fn ($query) => $query->whereOffice(strtoupper($this->office)))
                    ->get()
                : [],
            'offices' => $this->employee->model()->select('office')->distinct('office')->get()->map->office->prepend('')->unique(),
        ]);
    }
}
