<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Middleware\ValidateImports;
use App\Http\Requests\ImportRequest;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TimeLogController extends Controller
{

    public function __construct(
        private EmployeeService $employees,
        private ScannerService $scanners,
    ) {
        // $this->middleware(ValidateImports::class)->only('store');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): RedirectResponse|Response
    {
        return inertia('TimeLogs/Index', [
            'scanners' => $this->scanners->get(),
            'employees' => $this->employees->get(),
            'offices' => $this->employees->offices(),
            'month' => today()->startOfMonth()->format('Y-m'),
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
