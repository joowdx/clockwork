<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Scanner;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user=  $request->user();

        $scanners = match($user->type) {
            UserRole::ADMINISTRATOR, UserRole::DEVELOPER, UserRole::USER => $user->scanners,
            UserRole::DEPARTMENT_HEAD => Scanner::with(['lastUpload', 'latestTimelog'])->whereHas('employees', fn ($q) => $q->whereIn('office', $user->offices))->get(),
            default => [],
        };

        return [
            'app' => [
                'name' => config('app.name'),
                'time' => now(),
                'version' => app()->version(),
            ],
            'auth' => [
                'user' => $user->load(['employee'])->makeHidden(['type', 'disabled'])->toArray(),
                'guard' => collect(array_keys(config('auth.guards')))->first(fn ($g) => auth()->guard($g)->check()),
            ],
            'uptime' => now()->diffForHumans(config('app.start_time'), true),
            'timelogs' => [
                'last_update' => ($uploads = $scanners->pluck('lastUpload')->filter()->map->time->toArray()) ? max($uploads) : null,
                'latest_data' => ($timelogs = $scanners->pluck('latestTimelog')->filter()->map->time->toArray()) ? max($timelogs) : null,
                'scanners_last_update' => $scanners->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->lastUpload?->time]),
                'scanners_latest_data' => $scanners->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->latestTimelog?->time]),
            ],
        ];
    }
}
