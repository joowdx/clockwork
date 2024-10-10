<?php

namespace App\Jobs;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Office;
use App\Models\Scanner;
use App\Models\User;
use App\Traits\FormatsName;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

class ImportData implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, FormatsName,  InteractsWithQueue, Queueable, SerializesModels;

    protected array $uniqueColumns = [
        'first_name',
        'middle_name',
        'last_name',
        'qualifier_name',
    ];

    protected array $requiredColumns = [
        'first_name',
        'middle_name',
        'last_name',
        'qualifier_name',
    ];

    protected array $optionalColumns = [
        'active',
        'sex',
        'status',
        'substatus',
        'offices',
        'groups',
    ];

    protected array $aliasColumns = [
        'first_name' => ['given_name', 'forename'],
        'middle_name' => ['middle_initial'],
        'last_name' => ['family_name', 'surname'],
        'qualifier_name' => ['name_extension'],

        'offices' => ['office'],
        'groups' => ['group'],
    ];

    private readonly User $user;

    public function __construct(
        private readonly string $fileName,
        private readonly array $importOptions = [],
        private readonly int $chunkSize = 1000,
        private readonly bool $notify = true,
    ) {
        $this->user = Auth::user();

        $this->queue = 'main';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invalidDataException = new class extends Exception
        {
            public function __construct(
                public readonly ?string $title = null,
                public readonly ?string $body = null
            ) {
                parent::__construct();
            }
        };

        try {
            if (is_file($this->fileName) === false) {
                throw new $invalidDataException('Invalid file', 'The file uploaded is not recognized as a valid file.');
            }

            $csvReader = Reader::createFromPath($this->fileName)
                ->addFormatter(fn ($row) => array_map('mb_strtolower', $row))
                ->addFormatter(fn ($row) => array_map('trim', $row))
                ->setHeaderOffset(0);

            $mappedColumns = $this->getColumnMapping($csvReader->getHeader());

            $csvReader = $csvReader->mapHeader($mappedColumns);

            if (! empty(array_diff($this->requiredColumns, $mappedColumns))) {
                throw new $invalidDataException('Invalid file', 'The file uploaded is not recognized as a valid file.');
            }

            $knownColumns = array_merge($this->requiredColumns, $this->optionalColumns);

            $scannerColumns = array_values(array_diff($csvReader->getHeader(), $knownColumns));

            if ($scannerColumns !== array_filter($scannerColumns)) {
                throw new $invalidDataException('Empty column', 'The file uploaded contains an empty column.');
            }

            $lazyCollect = fn ($iterable) => LazyCollection::make(fn () => yield from $iterable);

            $duplicateEmployees = $lazyCollect($csvReader->getRecords($this->uniqueColumns))
                ->map(fn ($record) => trim(implode(' ', $record)))
                ->duplicates();

            if ($duplicateEmployees->isNotEmpty()) {
                throw new $invalidDataException('Duplicate employee found', str($duplicateEmployees->join('<br>'))->toHtmlString());
            }

            $duplicateUids = $lazyCollect($scannerColumns)
                ->mapWithKeys(fn ($column) => [$column => $lazyCollect($csvReader->fetchColumnByName($column))])
                ->map(function ($scanner) {
                    $duplicates = $scanner->filter()->duplicates()->values()->toArray();

                    return count($duplicates) ? $duplicates : null;
                })
                ->filter();

            if ($duplicateUids->isNotEmpty()) {
                $duplicates = $duplicateUids->map(function ($duplicates, $scanner) {
                    return "<b>{$scanner}:</b> ".implode(', ', $duplicates);
                });

                throw new $invalidDataException('Duplicate UID found', str($duplicates->join('<br>'))->toHtmlString());
            }

            DB::transaction(function () use ($csvReader, $invalidDataException, $scannerColumns) {
                $existingScanners = Scanner::withoutGlobalScopes()
                    ->when(
                        ! empty($scannerColumns),
                        function ($query) use ($scannerColumns) {
                            foreach ($scannerColumns as $scanner) {
                                $query->orWhereRaw('LOWER(name) = ?', $scanner);
                            }

                            return $query;
                        },
                        fn ($query) => $query->whereRaw('1 = 0')
                    )
                    ->pluck('id', 'name')
                    ->toArray();

                if (
                    true ||
                    isset($this->importOptions['scanner-autocreate']) &&
                    $this->importOptions['scanner-autocreate']
                ) {
                    $newScanners = collect($scannerColumns)
                        ->diff(array_flip($existingScanners))
                        ->mapWithKeys(fn ($scanner) => [$scanner => strtolower(str()->ulid())])
                        ->toArray();

                    Scanner::insert(collect($newScanners)->map(fn ($id, $scanner) => ['id' => $id, 'name' => $scanner])->toArray());

                    $existingScanners = array_merge($existingScanners, $newScanners);
                }

                $trashedScanners = Scanner::withoutGlobalScopes()
                    ->whereIn('id', $existingScanners)
                    ->onlyTrashed()
                    ->pluck('id', 'name')
                    ->toArray();

                $existingScanners = array_diff($existingScanners, $trashedScanners);

                try {
                    $groups = collect($csvReader->fetchColumnByName('groups'))
                        ->filter()
                        ->flatMap(fn ($g) => str_getcsv($g))
                        ->unique()
                        ->map(fn ($g) => trim($g))
                        ->filter()
                        ->values();
                } catch (InvalidArgument) {
                    $skipGroups = true;
                }

                if (! @$skipGroups) {
                    $existingGroups = Group::withoutGlobalScopes()
                        ->when(
                            $groups->isNotEmpty(),
                            function ($query) use ($groups) {
                                foreach ($groups as $groups) {
                                    $query->orWhereRaw('LOWER(name) = ?', $groups);
                                }

                                return $query;
                            },
                            fn ($query) => $query->whereRaw('1 = 0'),
                        )
                        ->pluck('id', 'name')
                        ->toArray();

                    if (
                        true ||
                        isset($this->importOptions['group-autocreate']) &&
                        $this->importOptions['group-autocreate'] &&
                        $groups->diff(array_keys($existingGroups))->isNotEmpty()
                    ) {
                        $newGroups = $groups->diff(array_keys($existingGroups))
                            ->mapWithKeys(fn ($office) => [$office => strtolower(str()->ulid())])
                            ->toArray();

                        Group::insert(collect($newGroups)->map(fn ($id, $group) => ['id' => $id, 'name' => $group])->toArray());

                        $existingGroups = array_merge($existingGroups, $newGroups);
                    }

                    $trashedGroups = Group::withoutGlobalScopes()
                        ->whereIn('id', $existingGroups)
                        ->onlyTrashed()
                        ->pluck('id', 'name')
                        ->toArray();

                    $existingGroups = array_diff($existingGroups, $trashedGroups);

                }

                try {
                    $offices = collect($csvReader->fetchColumnByName('offices'))
                        ->filter()
                        ->flatMap(fn ($o) => str_getcsv($o))
                        ->unique()
                        ->map(fn ($o) => mb_strtoupper(trim($o)))
                        ->filter()
                        ->values();
                } catch (InvalidArgument) {
                    $skipOffices = true;
                }

                if (! @$skipOffices) {
                    $existingOffices = Office::withoutGlobalScopes()
                        ->when(
                            $offices->isNotEmpty(),
                            function ($query) use ($offices) {
                                foreach ($offices as $office) {
                                    $query->orWhereRaw('UPPER(code) = ?', $office);
                                }

                                return $query;
                            },
                            fn ($query) => $query->whereRaw('1 = 0'),
                        )
                        ->pluck('id', 'code')
                        ->toArray();

                    if (
                        true ||
                        isset($this->importOptions['office-autocreate']) &&
                        $this->importOptions['office-autocreate'] &&
                        $offices->diff(array_keys($existingOffices))->isNotEmpty()
                    ) {
                        $newOffices = $offices->diff(array_keys($existingOffices))
                            ->mapWithKeys(fn ($office) => [$office => strtolower(str()->ulid())])
                            ->toArray();

                        Office::insert(collect($newOffices)->map(fn ($id, $office) => ['id' => $id, 'code' => $office, 'name' => $office])->toArray());

                        $existingOffices = array_merge($existingOffices, $newOffices);
                    }

                    $trashedOffices = Office::withoutGlobalScopes()
                        ->whereIn('id', $existingOffices)
                        ->onlyTrashed()
                        ->pluck('id', 'code')
                        ->toArray();

                    $existingOffices = array_diff($existingOffices, $trashedOffices);
                }

                foreach ($csvReader->getRecords() as $row) {
                    if (empty($row['last_name']) && empty($row['first_name'])) {
                        throw new $invalidDataException('Invalid data', 'First name and last name are required.');
                    }

                    $name = array_map([$this, 'formatName'], array_intersect_key($row, array_flip($this->uniqueColumns)));

                    if (
                        Employee::withoutGlobalScopes()
                            ->where($name)
                            ->whereNotNull('deleted_at')
                            ->exists()
                    ) {
                        continue;
                    }

                    $statusMapper = function (?string $status) {
                        $status = str($status)
                            ->squish()
                            ->replace(' ', '-')
                            ->replace('.', '')
                            ->toString();

                        $mapped = match ($status) {
                            'regular' => 'permanent',
                            'cos' => 'contractual',
                            'jo' => 'contractual',
                            'contract-of-service' => 'contractual',
                            'job-order' => 'contractual',
                            default => ''
                        };

                        return EmploymentStatus::tryFrom($status) ?? EmploymentStatus::from($mapped);
                    };

                    $substatusMapper = function (?string $substatus, ?string $status = null) use ($statusMapper) {
                        $substatus = str(empty($substatus) && $statusMapper($status) === EmploymentStatus::CONTRACTUAL ? $status : $substatus)
                            ->squish()
                            ->replace(' ', '-')
                            ->replace('.', '')
                            ->toString();

                        $mapped = match ($substatus) {
                            'cos' => 'contract-of-service',
                            'jo' => 'job-order',
                            default => '',
                        };

                        return EmploymentSubstatus::tryFrom($substatus) ?? EmploymentSubstatus::from($mapped);
                    };

                    $activeMapper = function ($value) {
                        return trim($value) === '' ? null : (bool) $value;
                    };

                    $data = array_intersect_key(
                        [
                            ...$row,
                            'status' => $statusMapper(@$row['status']),
                            'substatus' => $substatusMapper(@$row['substatus'], @$row['status']),
                            'active' => $activeMapper(@$row['active']),
                        ],
                        array_flip(Employee::make()->getFillable())
                    );

                    $employee = Employee::withoutGlobalScopes()->updateOrCreate(
                        $name,
                        collect($data)
                            ->reject(fn ($value, $key) => $key === 'active' && is_null($value))
                            ->toArray(),
                    );

                    $enrollments = collect(array_intersect_key($row, $existingScanners));

                    // DELETE EMPLOYEE ENROLLMENTS
                    // $employee->scanners()->detach($enrollments->filter()->map(fn ($uid, $scanner) => $existingScanners[$scanner])->toArray());

                    // INSERTS AND OVERWRITES CONFLICTING ENROLLMENTS
                    Enrollment::upsert(
                        $enrollments->filter()->map(fn ($uid, $scanner) => [
                            'scanner_id' => $existingScanners[$scanner],
                            'employee_id' => $employee->id,
                            'uid' => $uid,
                        ])->values()->toArray(),
                        ['scanner_id', 'uid'],
                        ['employee_id'],
                    );

                    Scanner::find($existingScanners)->lazy()->each(fn ($scanner) => $scanner->enrollments()->update(['device' => $scanner->uid]));

                    if (! @$skipGroups) {
                        $employee->groups()->sync(
                            collect(str_getcsv($row['groups']))
                                ->map(fn ($group) => mb_strtolower(trim($group)))
                                ->filter(fn ($group) => isset($existingGroups[$group]))
                                ->map(fn ($group) => $existingGroups[$group])
                        );
                    }

                    if (! @$skipOffices) {
                        $employee->offices()->sync(
                            collect(str_getcsv($row['offices']))
                                ->map(fn ($office) => mb_strtoupper(trim($office)))
                                ->filter(fn ($office) => isset($existingOffices[$office]))
                                ->map(fn ($office) => $existingOffices[$office])
                        );
                    }
                }

                Notification::make()
                    ->success()
                    ->title('Data import successful')
                    ->body('Data has been successfully imported.')
                    ->sendToDatabase($this->user);
            });
        } catch (Exception $exception) {
            if ($exception instanceof $invalidDataException) {
                Notification::make()
                    ->danger()
                    ->title($exception->title)
                    ->body($exception->body)
                    ->sendToDatabase($this->user);

                return;
            }

            Notification::make()
                ->danger()
                ->title('Data import failed')
                ->body($exception->getMessage())
                ->sendToDatabase($this->user);
        }
    }

    public function getColumnMapping(array $fileHeaders)
    {
        $aliases = collect($this->aliasColumns)
            ->flatMap(fn ($aliases, $key) => collect($aliases)->mapWithKeys(fn ($alias) => [$alias => $key])->toArray())
            ->toArray();

        $columnMapping = collect($fileHeaders)->map(function ($header) use ($aliases) {
            $header = (string) str($header)->lower()->replace(' ', '_');

            $knownColumns = array_merge($this->requiredColumns, $this->optionalColumns);

            if (in_array($header, $knownColumns)) {
                return $header;
            }

            if (in_array($header, array_merge(...array_values($this->aliasColumns)))) {
                return $aliases[$header];
            }

            return $header;
        });

        return $columnMapping->toArray();
    }
}
