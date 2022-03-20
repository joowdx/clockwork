<?php

namespace App\Services;

use App\Contracts\Import;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class EmployeeService implements Import
{
    protected $headers = [
        'SCANNER ID',
        'FAMILY NAME',
        'GIVEN NAME',
        'MIDDLE INITIAL',
        'NAME EXTENSION',
        'REGULAR',
        'OFFICE',
        'ACTIVE',
    ];

    public function __construct(
        private EmployeeRepository $repository
    ) { }

    public function parse(UploadedFile $file): void
    {
        $this->truncate();

        File::lines($file)
            ->skip(1)
            ->filter()
            ->map(fn($e) => str($e)->explode(','))
            ->map(fn ($e) => $this->repository->transformImportData($e->toArray(), $this->headers((string) File::lines($file)->first())))
            ->chunk(1000)
            ->each(fn ($e) => $this->repository->upsert($e->toArray(), ['biometrics_id', 'user_id']));
    }

    public function headers(string $line): array
    {
        return array_flip(explode(',', strtoupper(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line))));
    }

    public function all()
    {
        return $this->repository->get(true)->where('user_id', auth()->id())->get();
    }

    public function offices()
    {
        return $this->repository->get(true)->select(['office', 'name'])->get()->map->office->unique()->prepend('ALL');
    }

    public function truncate()
    {
        return $this->repository->get(true)->where('user_id', auth()->id())->delete();
    }

    public function markInactive()
    {

    }
}
