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
            ->whereShared(true)
            ->orWhereHas('users', fn ($q) => $q->whereUserId(auth()->id()))
            ->get();
    }
}
