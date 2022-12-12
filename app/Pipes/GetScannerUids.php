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

        $existing = $this->scanner->nameAsKeysForId(owned: false);

        return $next($request->flatMap(function ($entry) use ($scanners, $existing) {
            return collect($entry['scanners'])->map(function ($uid, $scanner) use ($entry, $scanners, $existing) {
                return [
                    'id' => str()->orderedUuid()->toString(),
                    'employee_id' => $entry['employee']['id'],
                    'scanner_id' => @$scanners[strtoupper($scanner)] ??
                        (
                            ! array_key_exists(strtoupper($scanner), $existing)
                            ? $this->create($scanner)
                            : null
                        ),
                    'uid' => $uid,
                ];
            })->filter(fn ($enrollment) => $enrollment['scanner_id'])->toArray();
        }));
    }

    private function create(string $name): Scanner
    {
        return app(ScannerRepository::class)->create([
            'name' => $name,
        ]);
    }
}
