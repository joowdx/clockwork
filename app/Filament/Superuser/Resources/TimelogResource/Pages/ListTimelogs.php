<?php

namespace App\Filament\Superuser\Resources\TimelogResource\Pages;

use App\Filament\Actions\ImportTimelogsAction;
use App\Filament\Superuser\Resources\TimelogResource;
use Filament\Resources\Pages\ListRecords;

class ListTimelogs extends ListRecords
{
    protected static string $resource = TimelogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportTimelogsAction::make(),
        ];
    }
}
