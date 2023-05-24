<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Requests\TimeLog\StoreRequest;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\User;
use App\Services\OfficeService;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ScannerService $scanner, TimeLogService $timelog, OfficeService $office): RedirectResponse|Response
    {
        return inertia('TimeLogs/Index2', [
            // 'scanners' => Inertia::lazy(fn () => $scanner->get()),
            // 'employees' => Inertia::lazy(fn () => $employee->get()),
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

                        if ($request->filled('active')) {
                            $query->whereActive($request->active);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Requests\TimeLog\StoreRequest  $request
     * @param  App\Contracts\Import  $import
     */
    public function store(StoreRequest $request, Import $import): RedirectResponse
    {
        $import->parse($request->file);

        return redirect()->back();
    }
}
