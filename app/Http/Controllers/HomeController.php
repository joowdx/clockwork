<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\Request;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ScannerService $scanner, TimeLogService $timelog): Response
    {
        return inertia('Home/Index', [
            ...$request->except(['page', 'paginate', 'search']),
            'scanners' => $scanner->get(),
            'search' => $request->search,
            'paginate' => $request->paginate ?? 50,
            'employees' => Employee::search($request->search)
                ->query(
                    function ($query) use ($request) {
                        $query
                            ->orderBy('name->last')
                            ->orderBy('name->first')
                            ->orderBy('name->middle')
                            ->select(['id', 'name', 'office', 'regular', 'groups'])
                            ->when(
                                $request->unenrolled,
                                fn ($q) => $q->whereDoesntHave('scanners'),
                                function ($query) {
                                    $query->whereHas('scanners', function ($query) {
                                        $query->whereHas('users', function ($query) {
                                            $query->where('user_id', auth()->id());
                                        });
                                    });
                                }
                            );

                        if ($request->filled('active') || $request->missing('active')) {
                            $query->whereActive($request->active ?? true);
                        }

                        if ($request->filled('office')) {
                            $query->whereOffice(strtoupper($request->office));
                        }

                        if ($request->filled('regular')) {
                            $query->whereRegular($request->regular);
                        }

                        if ($request->filled('group')) {
                            $query->whereJsonContains('groups', strtoupper($request->group));
                        }
                    },
                )
                ->paginate($request->paginate ?? 50)
                ->withQueryString()
                ->appends('query', null)
                ->through(fn ($employee) => [
                    'id' => $employee->id,
                    'name' => ucwords(mb_strtolower($employee->name_format->fullStartLastInitialMiddle)),
                    'status' => $employee->regular ? 'regular' : 'non-regular',
                    'office' => mb_strtolower($employee->office),
                    'groups' => collect($employee->groups)->map(fn ($g) => mb_strtolower($g))->join(', '),
                ]),
            'offices' => Employee::whereHas('scanners', function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            })->orderBy('office')
                ->pluck('office')
                ->unique()
                ->sort()
                ->values()
                ->map(fn ($g) => strtolower($g))
                ->toArray(),
            'groups' => Employee::whereHas('scanners', function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            })->pluck('groups')
                ->flatten()
                ->unique()
                ->values()
                ->map(fn ($g) => strtolower($g))
                ->sort()
                ->values()
                ->toArray(),
            ...$timelog->dates(),
        ]);
    }
}
