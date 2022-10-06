<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Requests\TimeLog\StoreRequest;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(EmployeeService $employee, ScannerService $scanner, TimeLogService $timelog): RedirectResponse|Response
    {
        return inertia('TimeLogs/Index', [
            'scanners' => $scanner->get(),
            'employees' => $employees = $employee->get(),
            'offices' => auth()->user()->administrator
                ? $employee->offices(true)
                : $employees->map->office->unique()->filter()->sort()->values(),
            ...$timelog->dates(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Requests\TimeLog\StoreRequest  $request
     * @param  App\Contracts\Import  $import
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, Import $import): RedirectResponse
    {
        $import->parse($request->file);

        return redirect()->back();
    }
}
