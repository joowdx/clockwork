<?php

namespace App\Filament\Superuser\Resources\DumpResource\Pages;

use App\Filament\Superuser\Resources\DumpResource;
use App\Jobs\DumpDatabase;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDumps extends ListRecords
{
    protected static string $resource = DumpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dump database')
                ->requiresConfirmation()
                ->successNotificationTitle('We will notify you once the database has been dumped.')
                ->modalIcon('heroicon-o-archive-box-arrow-down')
                ->modalDescription(
                    str(<<<HTML
                        This will dump the database to disk as backup. <br> <br>
                        Please note that dumping is a heavy operation which may take a while to complete and this is already automated to run every month.
                    HTML)
                    ->toHtmlString()
                )
                ->action(function ($action) {
                    DumpDatabase::dispatch();

                    $action->sendSuccessNotification();
                }),
        ];
    }
}
