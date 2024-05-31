<?php

namespace App\Filament\Superuser\Resources\SignatureResource\Pages;

use App\Filament\Superuser\Resources\SignatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSignature extends EditRecord
{
    protected static string $resource = SignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (str($data['specimen'])->startsWith('livewire-tmp/')) {
            if (file_exists(storage_path('app/'.$data['specimen']))) {
                $file = 'signatures/specimens/'.str($data['specimen'])->afterLast('/');

                if (! is_dir(storage_path('app/signatures/specimens'))) {
                    mkdir(storage_path('app/signatures/specimens'), recursive: true);
                }

                if (file_exists(storage_path('app/'.$this->record->specimen))) {
                    unlink(storage_path('app/'.$this->record->specimen));
                }

                rename(storage_path('app/'.$data['specimen']), storage_path('app/'.$file));

                $data['specimen'] = $file;
            }
        }

        if (str($data['certificate'])->startsWith('livewire-tmp/')) {
            if (file_exists(storage_path('app/'.$data['certificate']))) {
                $file = 'signatures/certificates/'.str($data['certificate'])->afterLast('/');

                if (! is_dir(storage_path('app/signatures/certificates'))) {
                    mkdir(storage_path('app/signatures/certificates'), recursive: true);
                }

                if (file_exists(storage_path('app/'.$this->record->certificate))) {
                    unlink(storage_path('app/'.$this->record->certificate));
                }

                rename(storage_path('app/'.$data['certificate']), storage_path('app/'.$file));

                $data['certificate'] = $file;
            }
        }

        return $data;
    }
}
