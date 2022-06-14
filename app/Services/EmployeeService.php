<?php

namespace App\Services;

use App\Actions\FileImport\InsertEmployees;
use App\Actions\FileImport\InsertEnrollments;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EmployeeService implements Import
{
    const REQUIRED_HEADERS = [
        'last name',
        'first name',
        'regular',
    ];

    const OPTIONAL_HEADERS = [
        'name extension',
        'middle name',
        'office',
        'active',
    ];

    private string $error = '';

    protected $header = [];

    public function __construct(
        private Repository $repository,
        private ScannerService $scanner,
    ) { }

    public function validate(Request $request): bool
    {
        $header = collect(self::REQUIRED_HEADERS)->every(fn ($header) => in_array($header, $this->headers((string) File::lines($request->file)->first())));

        if ($header) {
            $this->error = 'FILE UNSUPPORTED.';

            return false;
        }

        // $id = File::lines($file)->skip(1)->filter()->map(fn ($e) => str_getcsv($e)[$this->headers((string) File::lines($file)->first())['SCANNER ID']])->duplicates();

        // if ($id->isNotEmpty()) {
        //     $this->error = "DUPLICATE IDS DETECTED: {$id->values()->toJSON()}. PLEASE CHECK AGAIN.";

        //     return false;
        // }

        return true;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function parse(Request $request): void
    {
        $stream = File::lines($request->file);

        $scanners = $this->scanner->nameAsKeysForId();

        $stream->skip(1)
            ->filter()
            ->map(fn($e) => str_getcsv($e))
            ->map(fn ($e) => $this->transformImportData($e, $this->headers((string) $stream->first())))
            ->chunk(1000)
            ->each(function ($chunk) use ($scanners) {

                app(InsertEmployees::class)($chunk->toArray());

                $chunk->flatMap(function ($e) use ($scanners) {
                    return collect($e['scanners'])->filter()->map(function ($f, $k) use ($e, $scanners) {
                        return [
                            'uid' => $f,
                            'scanner_id' => $scanners[$k],
                            'employee_id' => $e['id'],
                            'id' => str()->orderedUuid(),
                        ];
                    })->toArray();
                })->chunk(1000)->each(fn ($chunk) => app(InsertEnrollments::class)($chunk->toArray()));
            });
    }

    public function get()
    {
        return $this->repository->query()->whereHas('scanners', function (Builder $query) {
            $query->whereHas('users', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
        })->sortByName()->get();
    }

    public function offices()
    {
        return $this->repository
            ->query()
            ->select(['office', 'name'])
            ->get()
            ->map
            ->office
            ->unique()
            ->prepend('ALL')
            ->filter()
            ->values();
    }

    public function update(Employee $employee, array $payload)
    {
        $this->repository->update($employee, $payload);
    }

    public function markInactive(Authenticatable|User $user)
    {
        $user->employees()->active()->whereDoesntHave('mainLogs', function ($q) {
            $q->whereDate('time', '>', today()->subMonth()->startOfMonth());
        })->update(['active' => 0]);
    }

    public function markActive(Authenticatable|User $user)
    {
        $user->employees()->active(0)->whereHas('mainLogs', function ($q) {
            $q->whereDate('time', '<', today()->subMonth()->startOfMonth());
        })->update(['active' => 1]);
    }

    public function loadLogs(Collection|array $employees, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= today()->startOfMonth();

        $to ??= today()->endOfMonth();

        if (is_array($employees)) {
            $employees = $this->repository->find($employees);
        }

        // $employees->filter->backedUp->map->load(['backupLogs' => fn ($q) => $q->whereBetween('time', [$from, $to])]);

        // $employees->reject->backedUp->map->load(['mainLogs' => fn ($q) => $q->whereBetween('time', [$from, $to])]);

        return $employees;
    }

    private  function transformImportData(array $line, array $headers): array
    {
        return [
            'id' => str()->orderedUuid()->toString(),
            'scanners' => $this->uids($line, $this->scanners($headers)),
            'name' => [
                'last' => $line[$headers['last name']],
                'first' => $line[$headers['first name']],
                'middle' => @$line[$headers['middle name']],
                'extension' => @$line[$headers['name extension']],
            ],
            'office' => @$line[$headers['office']],
            'regular' => (bool) $line[$headers['regular']],
            'active' => (bool) @$line[$headers['active']],
            'nameToJSON' => true,
        ];
    }

    private function headers(string $first): array
    {
        return array_flip(explode(',', strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $first))));
    }

    private function scanners(array $headers): array
    {
        return array_flip(array_diff(array_flip($headers), array_merge(self::REQUIRED_HEADERS, self::OPTIONAL_HEADERS)));
    }

    private function uids(array $line, array $scanners): array
    {
        return collect($scanners)->mapWithKeys(fn ($e, $f) => [$f => $line[$scanners[$f]]])->toArray();
    }
}
