<?php

namespace App\Filament\Developer\Resources\TokenResource\Pages;

use App\Filament\Developer\Resources\TokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTokens extends ListRecords
{
    protected static string $resource = TokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
