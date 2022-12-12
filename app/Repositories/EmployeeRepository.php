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
        return collect()->when(collect(@$payload['groups'])->isNotEmpty(), function ($data) use ($payload) {
            $groups = collect($payload['groups'])->filter()->map(fn ($group) => strtoupper($group))->values()->toArray();
            $data->put('groups', @$payload['toJSON'] ? json_encode($groups) : $groups);
        })->when(@$payload['name'], function ($data) use ($payload) {
            $name = [
                'last' => strtoupper($payload['name']['last']),
                'first' => strtoupper($payload['name']['first']),
                'middle' => strtoupper(@$payload['name']['middle']),
                'extension' => strtoupper(@$payload['name']['extension']),
            ];
            $data->put('name', @$payload['toJSON'] ? json_encode($name) : $name);
        })->when(@$payload['office'], function ($data) use ($payload) {
            $data->put('office', strtoupper(@$payload['office']));
        })->when(@$payload['regular'] !== null, function ($data) use ($payload) {
            $data->put('regular', (bool) $payload['regular']);
        })->when(@$payload['csc_format'] !== null, function ($data) use ($payload) {
            $data->put('csc_format', ((bool) @$payload['csc_format']) ?? true);
        })->when(@$payload['active'] !== null, function ($data) use ($payload) {
            $data->put('active', @$payload['active']);
        })->toArray();
    }
}
