<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Services\ScannerService;
use App\Services\TimelogService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request, ScannerService $scanner, TimelogService $timelog): mixed
    {
        return app(HomeController::class)->__invoke($request, $scanner, $timelog);
    }
}
