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
        return match ($query) {
            null => $this->get(),
            default => $this->repository->model()->search($query)->query(fn ($q) => $q->orderBy('name')->with('users'))->get()
        };
    }

    public function get(): Collection
    {
        return $this->repository->query()
            ->when(! request()->routeIs('scanners.index'), function ($query) {
                $query->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
                    ->when(auth()->user()->administrator, function ($query) {
                        $query->orWhere(fn ($q) => $q->whereShared(true));
                    })
                    ->orWhereDoesntHave('users');
            })
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
                    $query->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()));

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
