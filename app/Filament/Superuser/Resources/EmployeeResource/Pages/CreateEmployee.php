<?php

namespace App\Filament\Superuser\Resources\EmployeeResource\Pages;

use App\Filament\Superuser\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
