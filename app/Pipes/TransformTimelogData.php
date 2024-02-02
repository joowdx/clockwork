<?php

namespace App\Pipes;

use Illuminate\Support\Carbon;

class TransformTimelogData
{
    public function __construct(
        private string $scanner,
    ) {
    }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->map(function ($entry) {
            return [
                'uid' => trim($entry[0]),
                'scanner_id' => strtolower($this->scanner),
                'time' => Carbon::createFromTimeString($entry[1]),
                'state' => array_slice($entry, 2)[1],
            ];
        }));
    }
}
