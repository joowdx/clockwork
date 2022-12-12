<?php

namespace App\Pipes;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransformTimeLogData
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function handle(mixed $request, \Closure $next)
    {
        return $next($request->map(function ($entry) {
            return [
                'uid' => trim($entry[0]),
                'scanner_id' => strtolower($this->request->scanner),
                'time' => Carbon::createFromTimeString($entry[1]),
                'state' => implode('', collect(array_slice($entry, 2))->map(fn ($e) => $e > 1 ? 1 : $e)->toArray()),
            ];
        }));
    }
}
