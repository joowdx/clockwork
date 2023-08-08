<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ScannerService $scanner, TimeLogService $timelog): Response
    {
        $filter = function ($query) use ($request) {
            if ($request->all) {
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

        $employee = fn ($query) =>  $query
            ->with('scanners')
            ->orderBy('name->last')
            ->orderBy('name->first')
            ->orderBy('name->middle')
            ->orderBy('name->extension')
            ->where($filter)
            ->whereActive($request->active ?? true)
            ->when($request->filled('office'), fn ($q) => $q->whereOffice(strtolower($request->office)))
            ->when($request->filled('regular'), fn ($q) => $q->whereRegular($request->regular))
            ->when($request->filled('group'), fn ($q) => $q->whereJsonContains('groups', strtolower($request->group)));

        return inertia('Home/Index', [
            ...$request->except(['page', 'paginate', 'search']),
            'scanners' => $scanner->get(),
            'search' => $request->search,
            'paginate' => $request->paginate ?? 50,
            'employees' => Inertia::lazy(
                fn () => Employee::search($request->search)
                    ->query($employee)
                    ->paginate($request->paginate ?? 50)
                    ->withQueryString()
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
}
