<?php

namespace App\Services;

use App\Actions\FileImport\DeleteDuplicateEmployeeEnrollment;
use App\Actions\FileImport\InsertEmployees;
use App\Actions\FileImport\InsertEnrollments;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Pipes\CheckDuplicateUids;
use App\Pipes\CheckHeaders;
use App\Pipes\CheckNumericUid;
use App\Pipes\CheckRequiredFields;
use App\Pipes\Chunk;
use App\Pipes\GetScannerUids;
use App\Pipes\Sanitize;
use App\Pipes\SplitCsvString;
use App\Pipes\TransformEmployeeScannerData;
use App\Traits\ParsesEmployeeImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\File;

class EmployeeService implements Import
{
    use ParsesEmployeeImport;

    public function __construct(
        private Repository $repository,
    ) { }

    public function validate(Request $request): bool
    {
        return app(Pipeline::class)
            ->send((object) [
                'headers' => $this->headers((string) ($file = File::lines($request->file))->filter()->first()),
                'lines' => $file,
                'data' => app(SplitCsvString::class)->parse($file->filter()->skip(1)),
                'error' => null,
            ])
            ->through([
                CheckHeaders::class,
                CheckRequiredFields::class,
                CheckNumericUid::class,
                CheckDuplicateUids::class,
            ])->then(function ($result) {

                return $result->error ? ! ($this->error = $result->error) : true;

            });
    }

    public function error(): string
    {
        return $this->error;
    }

    public function parse(Request $request): void
    {
        app(Pipeline::class)
            ->send(File::lines($request->file))
            ->through([
                Sanitize::class,
                SplitCsvString::class,
                TransformEmployeeScannerData::class,
                Chunk::class,
            ])->then(fn ($d) => $d->each(function ($chunked) {

                app(InsertEmployees::class)($chunked->map->employee->toArray());

                app(Pipeline::class)
                    ->send($chunked)
                    ->through([
                        Sanitize::class,
                        GetScannerUids::class,
                        Chunk::class,
                    ])->then(fn ($d) => $d->each(function ($chunked) {

                        app(InsertEnrollments::class)($chunked->toArray());

                        app(DeleteDuplicateEmployeeEnrollment::class)();

                    })
                );
            })
        );
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
            ->filter()
            ->values();
    }

    public function update(Employee $employee, array $payload)
    {
        $this->repository->update($employee, $payload);
    }
}
