<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\EnrollmentRequest;
use App\Jobs\EnrollmentExport;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Scanner;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollment,
        private EmployeeRepository $employee,
        private ScannerRepository $scanner,
    ) {
    }

    public function index(Request $request)
    {
        $data = Enrollment::query()
            ->with(['employee:id,name', 'scanner:id,name'])
            ->addSelect([
                'identification' => Employee::selectRaw("TRIM(CONCAT_WS(' ', COALESCE(\"name\"->>'last', ''), COALESCE(\"name\"->>'first', ''), COALESCE(\"name\"->>'middle', ''), COALESCE(\"name\"->>'extension', ''))) AS name")
                    ->whereColumn('employee_id', 'employees.id')
                    ->limit(1)
            ])
            ->when($this->allowed(), function ($query) use ($request) {
                $query->whereHas('employee', fn ($q) => $q->whereIn('office', $request->user()->offices)->active());
            }, function ($query) {
                $query->whereHas('scanner', function ($query) {
                    $query->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                });
            })
            ->whereHas('employee', fn ($query) => $query->whereActive(true))
            ->where(function ($query) use ($request) {
                $query->when($request->search, function ($query) use ($request) {
                    $query->whereHas('scanner', fn ($q) => $q->where('name', 'ilike', "%$request->search%"))
                        ->orWhereHas('employee', fn ($q) => $q->where('name', 'ilike', "%$request->search%"));
                });
            })
            ->whereHas('scanner', fn ($q) => $q->whereEnabled(true))
            ->enabled()
            ->orderBy('identification')
            ->orderBy(
                Scanner::select('name')
                    ->whereColumn('scanner_id', 'scanners.id')
                    ->orderBy('name')
                    ->limit(1)
            )
            ->paginate($request->paginate ?? 25);

        return inertia('Enrollment/Index', [
            'data' => [
                ...$data->toArray(),
                'data' => $data->getCollection()
                    ->map(fn ($enrollment) => (object) [
                        'employee' => $enrollment->employee->name_format->fullStartLastInitialMiddle,
                        'scanner' => $enrollment->scanner->name,
                        'uid' => $enrollment->uid,
                    ])
                    ->groupBy(fn ($enrollment) => $enrollment->employee)
                    ->map(fn ($enrollments, $name) => $enrollments->prepend((object) ['header' => $name]))
                    ->values()
                    ->flatten()
                    ->toArray(),
            ],
            'paginate' => $request->paginate ?? 25,
            'search' => $request->search,
        ]);
    }

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

    public function destroy(Request $request, Enrollment $enrollment)
    {
        $this->confirmPassword($request->password);

        $this->enrollment->destroy($enrollment);

        return redirect()->back();
    }

    public function export(Request $request)
    {
        EnrollmentExport::dispatchSync($request->user());

        return Response::make(status: 200, headers: [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'. collect($request->user()->offices)->join('_') .'_enrollments.csv"',
        ]);
    }

    private function allowed()
    {
        return in_array(auth()->user()->role, [UserRole::DEPARTMENT_HEAD, UserRole::ADMINISTRATIVE_OFFICER]);
    }
}
