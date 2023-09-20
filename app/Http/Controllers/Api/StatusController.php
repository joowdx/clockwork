<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return [
            'app' => [
                'name' => config('app.name'),
                'time' => now(),
                'version' => app()->version(),
            ],
            'auth' => [
                'user' => $request->user()->load(['employee'])->makeHidden(['type', 'disabled'])->toArray(),
                'guard' => collect(array_keys(config('auth.guards')))->first(fn ($g) => auth()->guard($g)->check()),
            ],
            'uptime' => now()->diffForHumans(config('app.start_time'), true),
        ];
    }
}
