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
use Illuminate\Http\UploadedFile;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\File;

class EmployeeService implements Import
{
    use ParsesEmployeeImport;

    private Request $request;

    public function __construct(
        private Repository $repository,
    ) {
        $this->request = app(Request::class);
    }

    public function validate(UploadedFile $file): bool
    {
        return app(Pipeline::class)
            ->send((object) [
                'headers' => $this->headers((string) ($file = File::lines($file))->filter()->first()),
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

    public function parse(UploadedFile $file): void
    {
        app(Pipeline::class)
            ->send(File::lines($file))
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

                        $this->repository->all()->searchable();
                    })
                    );

                $this->repository->model()->unenrolled()->delete();
            })
            );
    }

    public function get(?bool $unenrolled = false)
    {
        return $this->repository->query()->when($unenrolled, function ($query) {
            $query->whereDoesntHave('scanners');
        }, function ($query) {
            $query->whereHas('scanners', function (Builder $query) {
                $query->whereHas('users', function (Builder $query) {
                    $query->where('user_id', auth()->id());
                });
            });
        })->get();
    }

    public function offices(bool $all = true)
    {
        return $this->repository
            ->query()
            ->select(['office', 'name'])
            ->whereHas('scanners', function (Builder $query) use ($all) {
                $query->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()));
                $query->when($all, function ($query) {
                    $query->orWhere(fn ($q) => $q->whereShared(true));
                });
            })
            ->get()
            ->map
            ->office
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    public function update(Employee $employee, array $payload)
    {
        $this->repository->update($employee, $payload);
    }
}
