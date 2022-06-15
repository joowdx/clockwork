<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollmentRequest;
use App\Models\Enrollment;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use App\Services\EnrollmentService;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollment,
        private EmployeeRepository $employee,
        private ScannerRepository $scanner,
    ) { }

    public function store(EnrollmentRequest $request)
    {
        $this->confirmPassword($request->password);

        $request->whenHas('employee', function () use ($request) {

            $this->enrollment->sync($this->employee->find($request->employee), $request->scanners);

        })->whenHas('scanner', function () use ($request) {

            $this->enrollment->sync($this->scanner->find($request->scanner), $request->employees);

        });

        return redirect()->back();
    }

    public function destroy(EnrollmentRequest $request, Enrollment $enrollment)
    {
        $this->confirmPassword($request->password);

        $this->enrollment->destroy($enrollment);

        return redirect()->back();
    }
}
