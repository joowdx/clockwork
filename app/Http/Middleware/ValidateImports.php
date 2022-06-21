<?php

namespace App\Http\Middleware;

use App\Contracts\Import;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidateImports
{
    public function __construct(
        private Import $import
    ) { }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('file')) {
            if (! $this->import->validate($request)) {
                throw ValidationException::withMessages(['file' => $this->import->error()]);
            }
        }

        return $next($request);
    }
}
