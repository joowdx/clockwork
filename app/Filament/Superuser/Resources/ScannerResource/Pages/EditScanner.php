<?php

namespace App\Filament\Superuser\Resources\ScannerResource\Pages;

use App\Actions\FlushScannerTimelogs;
use App\Filament\Superuser\Resources\ScannerResource;
use App\Models\Scanner;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditScanner extends EditRecord
{
    protected static string $resource = ScannerResource::class;

    public function getContentTabLabel(): ?string
    {
        return 'Scanner Settings';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make()
                    ->modalDescription(function (Scanner $record) {
                        if ($record->timelogs()->withoutGlobalScopes()->doesntExist()) {
                            return 'Are you sure you would like to do this?';
                        }

                        return "Are you sure you want to delete this scanner {$record->name}? This will also delete all related resources and cannot be undone.";
                    })
                    ->form([
                        TextInput::make('password')
                            ->hidden(fn (Scanner $record) => $record->timelogs()->withoutGlobalScopes()->doesntExist())
                            ->label('Password')
                            ->password()
                            ->currentPassword()
                            ->markAsRequired()
                            ->rules(['required']),
                    ]),
                Actions\Action::make('Flush')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to flush all the scanner\'s timelogs? This cannot be undone.')
                    ->form([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->currentPassword()
                            ->markAsRequired()
                            ->rules(['required']),
                    ])
                    ->action(function (Scanner $record, FlushScannerTimelogs $flusher) {
                        $flusher($record);

                        Notification::make()
                            ->warning()
                            ->title('Scanner timelogs are flushed')
                            ->send();
                    }),
                Actions\RestoreAction::make(),
            ]),
        ];
    }
}
