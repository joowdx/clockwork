<?php

namespace App\Repositories;

use App\Contracts\BaseRepository;
use App\Models\Model;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class ScannerRepository extends BaseRepository
{
    protected array $with = ['users'];

    protected function init(Builder &$builder): void
    {
        $builder->orderBy('name');
    }

    protected function transformData(array $payload): array
    {
        return [
            'name' => strtolower($payload['name'] ?? ''),
            'attlog_file' => @$payload['attlog_file'] ?: null,
            'remarks' => @$payload['remarks'],
            'shared' => (bool) @$payload['remarks'],
            'print_text_colour' => strtolower(@$payload['print_text_colour'] ?? ''),
            'print_background_colour' => strtolower(@$payload['print_background_colour'] ?? ''),
            'ip_address' => @$payload['ip_address'],
            'protocol' => @$payload['protocol'],
            'library' => @$payload['library'],
        ];
    }
}
