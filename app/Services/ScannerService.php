<?php

namespace App\Services;

use App\Contracts\Repository;

class ScannerService
{
    public function __construct(
        private Repository $repository
    ) { }

    public function get()
    {
        return $this->repository->query()
            ->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->orWhere(fn ($q) => $q->whereShared(true))
            ->get();
    }

    public function nameAsKeysForId()
    {
        return $this->repository->query()
            ->whereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($scanner) => [$scanner->name => $scanner->id])
            ->toArray();
    }
}
