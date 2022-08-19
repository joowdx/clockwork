<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficeService
{
    protected function database(): Builder
    {
        return DB::table('employees');
    }

    public function query(): Builder
    {
        return DB::table(
            $this->database()
                ->select('office as name')
                ->where('name', '<>', '', 'or')
                ->whereNotNull('name', 'or'),
        )->distinct('name');
    }

    public function get(): Collection
    {
        return $this->query()
            ->pluck('office');
    }
}
