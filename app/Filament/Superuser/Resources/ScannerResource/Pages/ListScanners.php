<?php

namespace App\Filament\Superuser\Resources\ScannerResource\Pages;

use App\Filament\Actions\ImportTimelogsAction;
use App\Filament\Superuser\Resources\ScannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScanners extends ListRecords
{
    protected static string $resource = ScannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                ImportTimelogsAction::make(),
                Actions\CreateAction::make(),
            ]),
        ];
    }
}
