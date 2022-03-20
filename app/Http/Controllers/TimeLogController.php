<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Requests\ImportRequest;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TimeLogController extends Controller
{

    public function __construct(
        // private RepositoryInterface $repository,
        private EmployeeService $employees,
    ) {
        // $this->middleware('validate.timelog')->only('store');
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
            'employees' => $this->employees->all(),
            'month' => today()->startOfMonth()->format('Y-m'),
            'offices' => $this->employees->offices(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Requests\ImportRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ImportRequest $request, Import $import): RedirectResponse
    {
        $import->parse($request->file);

        return redirect()->back();
    }
}
