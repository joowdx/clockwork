<?php

namespace App\Services;

use App\Contracts\Import;
use App\Contracts\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class TimeLogService implements Import
{
    public function __construct(
        private Repository $repository
    ) { }

    public function parse(UploadedFile $file): void
    {
        File::lines($file)
            ->filter()
            ->map(fn ($e) => explode("\t", $e))
            ->reject(fn ($e) => $this->latest()->gte($e[1]))
            ->map(fn ($e) => $this->repository->transformImportData($e))
            ->chunk(1000)
            ->map(fn ($e) => $e->toArray())
            ->map(fn ($e) => $this->repository->insert($e));
    }

    public function latest()
    {
        return request()->user()->latest?->time;
    }
}
