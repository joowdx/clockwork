<?php

namespace App\Services;

use App\Contracts\Repository;
use App\Models\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ScannerService
{
    public function __construct(
        private Repository $repository
    ) { }

    public function search(?string $query): Collection|LengthAwarePaginator
    {
        return $this->repository->model()->search($query)->query(fn ($q) => $q->orderBy('name')->with('users'))->get();
    }

    public function get(): Collection
    {
        return $this->repository->query()
            ->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->orWhere(fn ($q) => $q->whereShared(true))
            ->orWhere('created_by', auth()->id())
            ->get();
    }

    public function create(array $payload): Model
    {
        return $this->repository->create($payload);
    }

    public function update(Model $scanner, array $payload): Model
    {
        return $this->repository->update($scanner, $payload);
    }

    public function nameAsKeysForId(bool $owned = true): array
    {
        return $this->repository->query()
            ->when(! auth()->user()->administrator, function ($query) use ($owned) {
                $query->when($owned, function ($query) {
                    $query->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))->orWhere('created_by', auth()->id());

                    $query->orWhere('shared', true);
                });
            })
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($scanner) => [strtoupper($scanner->name) => $scanner->id])
            ->toArray();
    }

    public function destroy(Model $scanner)
    {
        $scanner->delete();
    }
}
