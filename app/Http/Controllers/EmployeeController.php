<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Http\Middleware\ValidateImports;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Models\Scanner;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employees,
        private Repository $repository,
    ) {
        $this->middleware(ValidateImports::class)->only('store');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return inertia('Employees/Index', [
            'employees' => $this->employees->all(),
            'scanners' => Scanner::all(),
            'offices' => $this->employees->offices(),
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
            $import->parse($request->file);
        } else {
            $this->repository->create([...$request->all(), 'user_id' => auth()->id()]);
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
    public function update(UpdateRequest $request, string $id)
    {
        $this->employees->update($id, $request->all());

        return redirect()->back();
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
