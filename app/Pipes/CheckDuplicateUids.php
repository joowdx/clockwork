<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckDuplicateUids
{
    use ParsesEmployeeImport;

    const ERROR = 'Duplicate UIDs detected.';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                ! collect($this->scanners($request->headers))
                    ->every(fn ($h) => $request->data->map(fn ($e) => $e[$h])
                        ->filter(fn ($e) => $e !== '')
                        ->duplicates()
                        ->isEmpty()
                    )
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
