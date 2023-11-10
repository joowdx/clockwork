<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Scanner;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $scanners = match ($user->role) {
            UserRole::ADMINISTRATOR, UserRole::DEVELOPER, UserRole::USER => $user->scanners,
            UserRole::DEPARTMENT_HEAD => Scanner::with(['lastUpload', 'latestTimelog'])->whereHas('employees', fn ($q) => $q->whereIn('office', $user->offices))->get(),
            default => [],
        };

        $filter = function ($query) use ($request) {
            if ($request->user()->role === UserRole::DEPARTMENT_HEAD) {
                $query->whereIn('office', $request->user()->offices);
            } elseif ($request->all) {
                if ($request->unenrolled === 'only') {
                    $query->whereDoesntHave('scanners');
                } elseif (! $request->unenrolled) {
                    $query->whereHas('scanners');
                }
            } else {
                $query->whereHas('scanners', function ($query) {
                    $query->where('enrollments.enabled', true);

                    $query->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                });
            }
        };

        $employee = fn ($query) => $query
            ->with('scanners')
            ->orderBy('name->last')
            ->orderBy('name->first')
            ->orderBy('name->middle')
            ->orderBy('name->extension')
            ->where($filter)
            ->whereActive($request->active ?? true)
            ->when($request->user()->role === UserRole::DEPARTMENT_HEAD, fn ($q) => $q->setEagerLoads([]))
            ->when($request->filled('office'), fn ($q) => $q->whereOffice(strtolower($request->office)))
            ->when($request->filled('regular'), fn ($q) => $q->whereRegular($request->regular))
            ->when($request->filled('group'), fn ($q) => $q->whereJsonContains('groups', strtolower($request->group)));

        return [
            'app' => [
                'name' => config('app.name'),
                'label' => config('app.label'),
                'version' => config('app.version'),
                'time' => now(),
            ],
            'auth' => [
                'user' => $user->load(['employee'])->makeHidden(['role', 'disabled'])->toArray(),
                'guard' => collect(array_keys(config('auth.guards')))->first(fn ($g) => auth()->guard($g)->check()),
            ],
            'uptime' => now()->diffForHumans(config('app.initiated'), true),
            'timelogs' => [
                'last_update' => ($uploads = $scanners->pluck('lastUpload')->filter()->map->time->toArray()) ? max($uploads) : null,
                'latest_data' => ($timelogs = $scanners->pluck('latestTimelog')->filter()->map->time->toArray()) ? max($timelogs) : null,
                'scanners_last_update' => $scanners->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->lastUpload?->time]),
                'scanners_latest_data' => $scanners->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->latestTimelog?->time]),
            ],
            'employees' => Employee::query($employee)->count(),
        ];
    }
}
