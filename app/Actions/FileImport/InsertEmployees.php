<?php

namespace App\Actions\FileImport;

use App\Repositories\EmployeeRepository;

class InsertEmployees
{
    public function __construct (
        private EmployeeRepository $repository
    ) { }

    public function __invoke(array $payload): void
    {
        $this->repository->upsert($payload, upserter: function ($payload, $transformed) {
            $this->repository->query()->upsert(
                collect($payload)->map(function ($e) {

                    return collect($e)->except('toJSON')->toArray();

                })->replaceRecursive($transformed)->toArray(), ['name'],
            );
        });
    }
}
