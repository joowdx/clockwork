<?php

namespace App\Filament\Superuser\Resources\SignatureResource\Pages;

use App\Filament\Superuser\Resources\SignatureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignature extends CreateRecord
{
    protected static string $resource = SignatureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (str($data['specimen'])->startsWith('livewire-tmp/')) {
            if (file_exists(storage_path('app/'.$data['specimen']))) {
                $file = 'signatures/specimens/'.str($data['specimen'])->afterLast('/');

                if (! is_dir(storage_path('app/signatures/specimens'))) {
                    mkdir(storage_path('app/signatures/specimens'), recursive: true);
                }

                rename(storage_path('app/'.$data['specimen']), storage_path('app/'.$file));

                $data['specimen'] = $file;
            } else {
                $data['specimen'] = null;
            }
        }

        if (str($data['certificate'])->startsWith('livewire-tmp/')) {
            if (file_exists(storage_path('app/'.$data['certificate']))) {
                $file = 'signatures/certificates/'.str($data['certificate'])->afterLast('/');

                if (! is_dir(storage_path('app/signatures/certificates'))) {
                    mkdir(storage_path('app/signatures/certificates'), recursive: true);
                }

                rename(storage_path('app/'.$data['certificate']), storage_path('app/'.$file));

                $data['certificate'] = $file;
            } else {
                $data['certificate'] = null;
            }
        }

        return $data;
    }

    protected function checkDirectories() {}
}
