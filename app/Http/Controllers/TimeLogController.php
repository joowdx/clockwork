<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Middleware\ValidateImports;
use App\Http\Requests\ImportRequest;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TimeLogController extends Controller
{

    public function __construct(
        private EmployeeService $employees,
        private ScannerService $scanners,
        private TimeLogService $timelog,
    ) {
        $this->middleware(ValidateImports::class)->only('store');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): RedirectResponse|Response
    {
        return inertia('TimeLogs/Index', [
            'scanners' => $this->scanners->get(),
            'employees' => $this->employees->get(),
            'offices' => $this->employees->offices(),
            ...$this->timelog->dates(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Requests\ImportRequest  $request
     * @param  App\Contracts\Import  $import
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ImportRequest $request, Import $import): RedirectResponse
    {
        $import->parse($request);

        return redirect()->back();
    }
}
