<?php

namespace App\Http\Controllers;

use App\Contracts\Repository;
use App\Events\EmployeesImported;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Jobs\ImportEmployees;
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
    ) {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Employee\StoreRequest  $request;
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        if ($request->has('file')) {

            ImportEmployees::dispatch(
                storage_path('app/' . $request->file->store()),
                $request->file->getClientOriginalName(),
                $request->user(),
                now(),
            );

            return redirect()->back();
        }

        $employee = $this->repository->create($request->all());

        return redirect()->back()->with('flash', [
            'employee' => $employee->load('scanners'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Employee $employee)
    {
        $this->employee->update($employee, $request->all());

        return redirect()->back()->with('flash', [
            'employee' => $employee->fresh(['scanners']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee, Request $request)
    {
        $this->confirmPassword($request->password);

        $this->repository->delete($employee);

        return redirect()->back();
    }
}
