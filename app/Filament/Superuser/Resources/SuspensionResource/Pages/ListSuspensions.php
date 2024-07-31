<?php

namespace App\Filament\Superuser\Resources\SuspensionResource\Pages;

use App\Filament\Actions\FetchHolidaysAction;
use App\Filament\Superuser\Resources\SuspensionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuspensions extends ListRecords
{
    protected static string $resource = SuspensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FetchHolidaysAction::make(),
            Actions\CreateAction::make()
                ->slideOver()
                ->requiresConfirmation()
                ->modalDescription('Adding a past date will require you to enter your password as this may have an irreversible side-effect.')
                ->modalWidth('xl')
                ->createAnother(false),
        ];
    }
}
