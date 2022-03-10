<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    /**
     * Provision a new web server.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        $from = today()->subMonth()->startOfMonth();
        $to = today()->subMonth()->endOfMonth();
        return view('dtr2', [
            'employees' => Employee::with(['logs' => fn ($q) => $q->whereBetween('time', [$from, $to])])->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
