<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['scanners'];

    protected function init(Builder &$builder): void
    {
        $builder->sortByName();
    }

    protected function transformData(array $payload): array
    {
        return collect()->when(true, function ($data) use ($payload) {
            $groups = collect(@$payload['groups'])->filter()->map(fn ($group) => trim(mb_strtolower($group)))->values()->toArray();
            $data->put('groups', @$payload['toJSON'] ? json_encode($groups) : $groups);
        })->when(@$payload['name'], function ($data) use ($payload) {
            $name = [
                'last' => trim(mb_strtolower($payload['name']['last'] ?? '')),
                'first' => trim(mb_strtolower($payload['name']['first'] ?? '')),
                'middle' => trim(mb_strtolower(@$payload['name']['middle'] ?? '')),
                'extension' => trim(mb_strtolower(@$payload['name']['extension'] ?? '')),
            ];
            $data->put('name', @$payload['toJSON'] ? json_encode($name) : $name);
        })->when(@$payload['office'], function ($data) use ($payload) {
            $data->put('office', trim(mb_strtolower(@$payload['office'] ?? '')));
        })->when(@$payload['regular'] !== null, function ($data) use ($payload) {
            $data->put('regular', (bool) $payload['regular']);
        })->when(@$payload['csc_format'] !== null, function ($data) use ($payload) {
            $data->put('csc_format', ((bool) @$payload['csc_format']) ?? true);
        })->when(@$payload['active'] !== null, function ($data) use ($payload) {
            $data->put('active', @$payload['active']);
        })->toArray();
    }
}
