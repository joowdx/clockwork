<?php

namespace App\Services;

use App\Contracts\Repository;
use App\Models\Model;
use Illuminate\Support\Collection;

class ScannerService
{
    public function __construct(
        private Repository $repository
    ) { }

    public function get(): Collection
    {
        return $this->repository->query()
            ->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->orWhere(fn ($q) => $q->whereShared(true))
            ->get();
    }

    public function update(Model $scanner, array $payload): Model
    {
        return $this->repository->update($scanner, $payload);
    }

    public function nameAsKeysForId(): array
    {
        return $this->repository->query()
            ->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->id])
            ->toArray();
    }
}
