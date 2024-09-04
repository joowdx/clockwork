<?php

namespace App\Filament\Secretary\Resources\EmployeeResource\Pages;

use App\Filament\Secretary\Resources\EmployeeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
