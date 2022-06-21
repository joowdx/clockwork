<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckHeaders
{
    use ParsesEmployeeImport;

    const ERROR = 'File is malformed.';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                ! collect(self::$requiredHeaders)
                    ->every(fn ($header) => in_array($header, array_flip($request->headers)))
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
