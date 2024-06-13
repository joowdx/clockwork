<?php

namespace App\Filament\Secretary\Resources\ScannerResource\Pages;

use App\Filament\Actions\ImportTimelogsAction;
use App\Filament\Secretary\Resources\ScannerResource;
use Filament\Resources\Pages\ListRecords;

class ListScanners extends ListRecords
{
    protected static string $resource = ScannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportTimelogsAction::make()
                ->onlyAssigned(),
        ];
    }
}
