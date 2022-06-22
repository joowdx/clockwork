<?php

namespace App\Pipes;

class Sanitize
{
    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->filter()->unique());
    }
}
