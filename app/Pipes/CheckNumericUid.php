<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckNumericUid
{
    use ParsesEmployeeImport;

    const ERROR = 'Invalid UID/s detected.';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                ! collect($this->scanners($request->headers))
                    ->every(fn ($h) =>
                        $request->data->map(fn ($e) => $e[$h])
                            ->filter()
                            ->every(fn ($e) => is_numeric($e))
                    )
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
