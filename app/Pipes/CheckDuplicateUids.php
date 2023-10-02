<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckDuplicateUids
{
    use ParsesEmployeeImport;

    const ERROR = 'Duplicate UIDs detected: ';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            $duplicates = collect($this->scanners($request->headers))
                ->map(fn ($h) => $request->data->map(fn ($e) => $e[$h])->filter()->duplicates()->values()->toArray()
            )->filter();

            if ($duplicates->isNotEmpty()) {
                $request->error = self::ERROR . $duplicates->toJson();
            }
        }

        return $next($request);
    }
}
