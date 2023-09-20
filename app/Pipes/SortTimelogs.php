<?php

namespace App\Pipes;

class SortTimelogs
{
    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->sortBy('time'));
    }
}
