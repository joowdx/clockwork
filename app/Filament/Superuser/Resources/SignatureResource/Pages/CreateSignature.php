<?php

namespace App\Filament\Superuser\Resources\SignatureResource\Pages;

use App\Actions\OptimizeImage;
use App\Filament\Superuser\Resources\SignatureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignature extends CreateRecord
{
    protected static string $resource = SignatureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['specimen'] = 'data:image/x-webp;base64,'.
            base64_encode(
                app(OptimizeImage::class)(base64_decode(explode(',', $data['specimen'])[1]))
            );

        return $data;
    }
}
