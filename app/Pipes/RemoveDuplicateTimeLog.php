<?php

namespace App\Pipes;

use Illuminate\Http\Request;

class RemoveDuplicateTimeLog
{
    public function __construct(
        private Request $request,
    ) { }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->unique(fn ($e) => $e['scanner_id'].$e['time'].$e['state']));
    }
}
