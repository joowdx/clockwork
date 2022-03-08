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
        $from = today()->subMonth(2);
        $to = today();
        return view('dtr2', [
            'employees' => Employee::with(['logs' => fn ($q) => $q->whereBetween('time', [$from, $to])])->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
