<?php

namespace App\Http\Controllers;

use App\Models\Scanner;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return inertia('Scanner/Index', [
            'employees' => Scanner::all(),
            'month' => today()->startOfMonth()->format('Y-m'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
