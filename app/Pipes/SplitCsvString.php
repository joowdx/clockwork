<?php

namespace App\Pipes;

class SplitCsvString
{
    public function parse(mixed $request)
    {
        return $request->map(fn ($e) => str_getcsv($e));
    }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($this->parse($request));
    }
}
