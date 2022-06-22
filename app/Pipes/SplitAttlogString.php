<?php

namespace App\Pipes;

class SplitAttlogString
{
    public function split(mixed $request)
    {
        return $request->map(fn ($e) => explode("\t", $e));
    }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($this->split($request));
    }
}
