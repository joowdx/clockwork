<?php

namespace App\Pipes;

class CheckStateEntries
{
    const ERROR = 'Invalid or malformed file.';

    public function handle(mixed $request, \Closure $next)
    {
        if (! $request->error) {
            if (
                ! $request->data->every(
                    fn ($entry) => implode('', collect(array_slice($entry, 2))->map(fn ($d) => preg_replace('/[0-9]+/', 0, $d))->toArray()) === '0000'
                )
            ) {
                $request->error = self::ERROR;
            }
        }

        return $next($request);
    }
}
