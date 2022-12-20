<?php

namespace App\Pipes;

class Chunk
{
    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->chunk(7500));
    }
}
