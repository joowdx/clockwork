<?php

namespace App\Services;

class SearchService
{
    public function get(array $data)
    {
        $employees = collect($data['employees']);

        dd($employees);
    }
}
