<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepository;
use App\Http\Requests\ScannerRequest;
use App\Models\Scanner;
use App\Services\ScannerService;
use Illuminate\Http\Request;

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
            'search' => $request->search,
            'scanners' => $this->scanner->search($request->search),
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
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Http\Response
     */
    public function show(Scanner $scanner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Scanner  $scanner
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
     * @param  \App\Models\Scanner  $scanner
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
     * @param  \App\Models\Scanner  $scanner
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
}
