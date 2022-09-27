<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckNumericUid
{
    use ParsesEmployeeImport;

    const ERROR = 'Invalid or malformed file. Non-numeric UIDs detected.';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                @$request->headers
                    ? ! collect($this->scanners($request->headers))->every(fn ($h) => $request->data->map->{$h}->filter()->every(fn ($e) => is_numeric($e)))
                    : ! $request->data->map->{0}->every(fn ($e) => is_numeric($e))
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
