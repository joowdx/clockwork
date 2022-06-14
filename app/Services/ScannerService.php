<?php

namespace App\Services;

use App\Contracts\Repository;
use App\Models\Scanner;

class ScannerService
{
    public function __construct(
        private Repository $repository
    ) { }

    public function get()
    {
        return $this->repository->query()
            ->orWhereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->whereShared(true)
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
