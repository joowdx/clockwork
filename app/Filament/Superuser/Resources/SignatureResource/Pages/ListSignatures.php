<?php

namespace App\Filament\Superuser\Resources\SignatureResource\Pages;

use App\Filament\Superuser\Resources\SignatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignatures extends ListRecords
{
    protected static string $resource = SignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
