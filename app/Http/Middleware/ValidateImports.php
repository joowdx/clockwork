<?php

namespace App\Http\Middleware;

use App\Contracts\Import;
use Closure;
use Illuminate\Http\Request;

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
            abort_unless($this->import->validate($request), '400', $this->import->error());
        }

        return $next($request);
    }
}
