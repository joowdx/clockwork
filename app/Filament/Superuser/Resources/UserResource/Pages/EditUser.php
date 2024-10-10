<?php

namespace App\Filament\Superuser\Resources\UserResource\Pages;

use App\Filament\Superuser\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        $this->form->fill([
            ...$this->form->getState(),
            'password' => null,
            'passwordConfirmation' => null,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
