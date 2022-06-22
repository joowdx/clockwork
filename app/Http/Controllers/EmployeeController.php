<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private Repository $repository,
        private EmployeeService $employee,
        private ScannerService $scanner,
    ) { }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function index()
    {
        return inertia('Employees/Index', [
            'employees' => $this->employee->get(),
            'scanners' => $this->scanner->get(),
            'offices' => $this->employee->offices(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Employee\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, Import $import)
    {
        if ($request->has('file')) {
            $import->parse($request);
        } else {
            $this->repository->create($request->all());
        }

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Employee\UpdateRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Employee $employee)
    {
        $this->employee->update($employee, $request->all());

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Employee\UpdateRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        return inertia('Employees/Edit', [
            'employee' => $employee->load('scanners'),
            'scanners' => $this->scanner->get(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, string $id)
    {
        $this->confirmPassword($request->password);

        $this->repository->destroy(explode(',', $id));

        return redirect()->back();
    }
}
