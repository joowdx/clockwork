<?php

namespace App\Pipes;

use App\Models\Scanner;
use App\Repositories\ScannerRepository;
use App\Services\ScannerService;
use App\Traits\ParsesEmployeeImport;

class GetScannerUids
{
    use ParsesEmployeeImport;

    public function __construct(
        private ScannerService $scanner,
    ) {
    }

    public function handle(mixed $request, \Closure $next)
    {
        $scanners = $this->scanner->nameAsKeysForId();

        return $next($request->flatMap(function ($entry) use ($scanners) {
            return collect($entry['scanners'])->map(function ($uid, $scanner) use ($entry, $scanners) {
                return [
                    'id' => strtolower(str()->ulid()),
                    'employee_id' => $entry['employee']['id'],
                    'scanner_id' => @$scanners[strtoupper($scanner)] ?? $this->create($scanner)->id,
                    'uid' => $uid,
                ];
            })->filter(fn ($enrollment) => $enrollment['scanner_id'])->toArray();
        }));
    }

    private function create(string $name): Scanner
    {
        return Scanner::firstOrCreate([
            'name' => strtoupper($name),
        ]);
    }
}
