<?php

namespace App\Http\Controllers;

use App\Contracts\ScannerDriver;
use App\Contracts\UserRepository;
use App\Drivers\TadPhp;
use App\Http\Requests\ScannerRequest;
use App\Models\Scanner;
use App\Services\ScannerService;
use App\Services\TimeLogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScannerController extends Controller
{
    public function __construct(
        private ScannerService $scanner,
        private UserRepository $user,
    ) {
        $this->authorizeResource(Scanner::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return inertia('Scanners/Index', [
            ...$request->except(['page', 'paginate', 'search']),
            'search' => $request->search,
            'paginate' => $request->paginate ?? 50,
            'scanners' => Scanner::search($request->search)
                ->query(fn ($q) => $q->select(['id', 'name', 'created_at'])->orderBy('name')->with(['users' => fn ($q) => $q->select('username')]))
                ->paginate($request->paginate ?? 50)
                ->withQueryString()
                ->appends('query', null)
                ->through(fn ($scanner) => [
                    'id' => $scanner->id,
                    'name' => $scanner->name,
                    'assignees' => $scanner->users->map->username,
                    'created_at' => $scanner->created_at->format('Y M d - H:i')
                ])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Scanners/Create', [
            'scanners' => $this->scanner->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ScannerRequest $request)
    {
        $scanner = $this->scanner->create($request->all());

        return redirect()->route('scanners.edit', $scanner->id);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Scanner $scanner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Scanner $scanner)
    {
        return inertia('Scanners/Edit', [
            'scanner' => $scanner->load('users'),
            'users' => $this->user->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ScannerRequest $request, Scanner $scanner)
    {
        $this->scanner->update($scanner, $request->all());

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(ScannerRequest $request, Scanner $scanner)
    {
        if ($request->timelogs) {
            $scanner->timelogs()->delete();

            return redirect()->back();
        }

        $this->scanner->destroy($scanner);

        return redirect()->route('scanners.index');
    }

    /**
     * Download attlogs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Contrants\ScannerDriver  $scanner
     * @param  \App\Actions\FileImport\InsertTimeLogs  $inserter
     * @return \Illuminate\Http\Response
     */
    public function download(Scanner $scanner, TimeLogService $service, ?ScannerDriver $driver)
    {
        if ($driver === null) {
            throw ValidationException::withMessages(['message' => 'Scanner is not configured.']);
        }

        try {
            $service->insert($driver->getFormattedAttlogs($scanner->id));
        } catch (ConnectionException  $exception) {
            throw ValidationException::withMessages(['message' => $exception->getMessage()]);
        }

        return redirect()->back();
    }

    public function syncTime(Scanner $scanner, ?ScannerDriver $driver)
    {
        if ($driver === null) {
            return redirect()->back()->withErrors([
                'message' => 'Please configure this device\'s driver.',
            ]);
        }

        if (! $driver instanceof TadPhp) {
            return redirect()->back()->withErrors([
                'message' => 'Driver '.$scanner->driver.' is not compatible.',
            ]);
        }

        $driver->syncTime();

        return redirect()->back();
    }
}
