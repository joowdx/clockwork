<?php

namespace App\Pipes;

use App\Services\ScannerService;
use App\Traits\ParsesEmployeeImport;

class GetScannerUids
{
    use ParsesEmployeeImport;

    public function __construct(
        private ScannerService $scanner,
    ) {}

    public function handle(mixed $request, \Closure $next)
    {
        $scanners = $this->scanner->nameAsKeysForId();

        return $next($request->flatMap(function ($entry) use ($scanners) {
            return collect($entry['scanners'])->map(function ($uid, $scanner) use ($entry, $scanners) {
                return [
                    'id' => str()->orderedUuid()->toString(),
                    'employee_id' => $entry['employee']['id'],
                    'scanner_id' => $scanners[$scanner],
                    'uid' => $uid,
                ];
            })->toArray();
        }));
    }
}
