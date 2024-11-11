<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPanelPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|\App\Models\Employee */
        $user = (Auth::guard()->user() ?? Auth::guard('employee')->user());

        abort_unless($user?->canAccessPanel(Filament::getCurrentPanel()) ?? true, 403);

        return $next($request);
    }
}
