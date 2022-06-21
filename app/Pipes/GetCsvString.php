<?php

namespace App\Pipes;

class GetCsvString
{
    public function parse(mixed $request)
    {
        return $request->filter()->unique()->map(fn ($e) => str_getcsv($e));
    }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($this->parse($request));
    }
}
