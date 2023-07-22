<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Requests\TimeLog\StoreRequest;
use Illuminate\Http\RedirectResponse;

class TimeLogController extends Controller
{
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
