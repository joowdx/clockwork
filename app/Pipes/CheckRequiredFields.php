<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class CheckRequiredFields
{
    use ParsesEmployeeImport;

    const ERROR = "Fields 'LAST NAME', 'FIRST NAME', and 'REGULAR' must not be blank or empty.";

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                ! collect($request->headers)
                    ->filter(fn ($i, $h) => in_array(strtolower($h), self::$requiredHeaders))
                    ->every(fn ($h) =>
                        $request->data->map(fn ($e) => $e[$h])
                            ->reject(fn ($e) => $e !== "")
                            ->isEmpty()
                    )
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
