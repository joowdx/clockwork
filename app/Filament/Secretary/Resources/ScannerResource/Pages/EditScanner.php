<?php

namespace App\Filament\Secretary\Resources\ScannerResource\Pages;

use App\Filament\Secretary\Resources\ScannerResource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
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
                Actions\DeleteAction::make()
                    ->form([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->currentPassword()
                            ->markAsRequired()
                            ->rules(['required', 'string']),
                    ]),
            ]),
        ];
    }
}
