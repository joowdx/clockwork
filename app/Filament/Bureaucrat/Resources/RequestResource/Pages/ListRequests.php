<?php

namespace App\Filament\Bureaucrat\Resources\RequestResource\Pages;

use App\Filament\Bureaucrat\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
