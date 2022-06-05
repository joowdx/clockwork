<?php

namespace App\Http\Controllers;

use App\Models\Scanner;
use App\Services\ScannerService;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function __construct(
        private ScannerService $scanners,
    ) { }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return inertia('Scanners/Index', [
            'scanners' => $this->scanners->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Scanners/Index', [
            'scanners' => $this->scanners->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Scanner $scanner)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Scanner $scanner)
    {
        //
    }
}
