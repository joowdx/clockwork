<?php

namespace App\Pipes;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransformTimeLogData
{
    public function __construct(
        private Request $request,
    ) { }

    public function handle(mixed $request, \Closure $next)
    {
        $enrollments = Enrollment::whereScannerId($this->request->scanner)
                ->whereIn('uid', $request->map->{0}->unique())->get();

        return $next($request->map(function ($entry) use ($enrollments) {
            return [
                'uid' => $uid = trim($entry[0]),
                'scanner_id' => strtolower($this->request->scanner),
                'enrollment_id' => $enrollments->first(fn ($e) => $e->uid == $uid)?->id,
                'time' => Carbon::createFromTimeString($entry[1]),
                'state' => join('', collect(array_slice($entry, 2))->map(fn ($e) => $e > 1 ? 1 : $e)->toArray()),
            ];
        }));
    }
}
