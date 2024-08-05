<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Timelog;
use App\Services\ScannerService;
use App\Services\TimelogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ScannerService $scanner, TimelogService $timelog): mixed
    {
        $filter = function ($query) use ($request) {
            if ($this->allowed()) {
                $query->whereIn('office', $request->user()->offices);
            } elseif ($request->all) {
                if ($request->unenrolled === 'only') {
                    $query->whereDoesntHave('scanners');
                } elseif (! $request->unenrolled) {
                    $query->whereHas('scanners');
                }
            } else {
                $query->whereHas('scanners', function ($query) {
                    $query->where('enrollments.enabled', true);

                    $query->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                });
            }
        };

        $employee = fn ($query) => $query
            ->with('scanners')
            ->orderBy('name->last')
            ->orderBy('name->first')
            ->orderBy('name->middle')
            ->orderBy('name->extension')
            ->where($filter)
            ->when($this->allowed(), fn ($q) => $q->active()->setEagerLoads([]), fn ($q) => $q->active(in_array(strtolower($request->active), ['true', '1'])))
            ->when($request->filled('office'), fn ($q) => $q->whereOffice(strtolower($request->office)))
            ->when($request->filled('regular'), fn ($q) => $q->whereRegular($request->regular))
            ->when($request->filled('group'), fn ($q) => $q->whereJsonContains('groups', strtolower($request->group)));

        if ($request->expectsJson()) {
            return [
                'employees' => Employee::search($request->search)
                    ->query($employee)
                    ->paginate($request->paginate ?? 5)
                    ->appends('query', null),
            ];
        }

        return inertia('Home/Index', [
            ...$request->except(['page', 'paginate', 'search']),
            'scanners' => $scanner->get(),
            'search' => $request->search,
            'paginate' => $request->paginate ?? 5,
            'employees' => Inertia::lazy(
                fn () => Employee::search($request->search)
                    ->query($employee)
                    ->paginate($request->paginate ?? 5)
                    ->appends('query', null)
            ),
            'offices' => Inertia::lazy(
                fn () => Employee::where($filter)
                    ->orderBy('office')
                    ->pluck('office')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->map(fn ($g) => strtolower($g))
                    ->toArray()
            ),
            'groups' => Inertia::lazy(
                fn () => Employee::where($filter)
                    ->when($request->filled('office'), fn ($q) => $q->whereOffice(strtolower($request->office)))
                    ->pluck('groups')
                    ->flatten()
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(fn ($g) => strtolower($g))
                    ->sort()
                    ->values()
                    ->toArray()
            ),
            ...$timelog->dates(),
        ]);
    }

    private function allowed()
    {
        return in_array(auth()->user()->role, [UserRole::DEPARTMENT_HEAD, UserRole::ADMINISTRATIVE_OFFICER]);
    }
}
