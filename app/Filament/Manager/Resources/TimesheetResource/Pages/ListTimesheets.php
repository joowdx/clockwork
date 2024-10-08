<?php

namespace App\Filament\Manager\Resources\TimesheetResource\Pages;

use App\Filament\Manager\Resources\TimesheetResource;
use Filament\Resources\Pages\ListRecords;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
