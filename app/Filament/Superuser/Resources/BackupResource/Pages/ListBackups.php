<?php

namespace App\Filament\Superuser\Resources\BackupResource\Pages;

use App\Filament\Superuser\Resources\BackupResource;
use App\Jobs\BackupDatabase;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backup database')
                ->requiresConfirmation()
                ->successNotificationTitle('We will notify you once the database has been backed up.')
                ->modalIcon('heroicon-o-archive-box-arrow-down')
                ->modalDescription(
                    str(<<<'HTML'
                        This will dump the database to disk as backup. <br> <br>
                        Please note that this is a heavy operation which may take a while to complete and this is already automated to run every month.
                    HTML)
                        ->toHtmlString()
                )
                ->action(function ($action) {
                    BackupDatabase::dispatch();

                    $action->sendSuccessNotification();
                }),
        ];
    }
}
