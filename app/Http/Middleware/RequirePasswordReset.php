<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->needsPasswordReset()) {
            return $request->expectsJson()
                ? abort(403, 'You are required to reset your password before you can proceed.')
                : redirect()->route('password-reset');
        }

        return $next($request);
    }
}
