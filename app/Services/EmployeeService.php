<?php

namespace App\Services;

use App\Contracts\Import;
use App\Contracts\Repository;
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
        private Repository $repository
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
            ->each(fn ($e) => $this->repository->insert($e->toArray()));
    }

    public function headers(string $line): array
    {
        return array_flip(explode(',', strtoupper(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line))));
    }

    public function all()
    {
        return $this->repository
            ->get(true)
            ->where('user_id', auth()->id())
            ->get();
    }

    public function offices()
    {
        return $this->repository
            ->get(true)
            ->where('user_id', auth()->id())
            ->select(['office', 'name'])
            ->get()
            ->map
            ->office
            ->unique()
            ->prepend('ALL')
            ->filter()
            ->values();
    }

    public function truncate()
    {
        return $this->repository->get(true)->where('user_id', auth()->id())->delete();
    }

    public function update(string $id, array $payload)
    {
        $this->repository->update($id = explode(',', $id), [
            'user_id' => auth()->id(),
            ...$payload,
            'nameToJSON' => true,
        ], count($id) > 1 ? collect(request()->only('office', 'regular', 'active'))->filter(fn ($e) => $e == '*')->keys()->push('biometrics_id', 'name', 'user_id')->toArray() : []);
    }
}
