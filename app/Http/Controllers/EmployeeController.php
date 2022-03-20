<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Services\EmployeeService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employees,
        private Repository $repository,
    ) { }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return inertia('Employees/Index', [
            'employees' => $this->employees->all(),
            'month' => today()->startOfMonth()->format('Y-m'),
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
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, StatefulGuard $guard, string $id)
    {
        $confirmed = app(ConfirmPassword::class)(
            $guard, $request->user(), $request->password
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $this->repository->destroy(explode(',', $id));

        return redirect()->back();
    }
}
