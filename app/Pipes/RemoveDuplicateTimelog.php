<?php

namespace App\Pipes;

class RemoveDuplicateTimelog
{
    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->unique(fn ($e) => $e['scanner_id'].$e['time'].$e['state']));
    }
}
