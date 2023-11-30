<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrintRequest;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PrintRequest $request, DashboardService $service)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        return $service->getTardiesAndUndertimes($request->month);
    }
}
