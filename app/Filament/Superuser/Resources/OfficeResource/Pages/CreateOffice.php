<?php

namespace App\Filament\Superuser\Resources\OfficeResource\Pages;

use App\Actions\OptimizeImage;
use App\Filament\Superuser\Resources\OfficeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->logo && file_exists($this->record->icon)) {
            $optimized = app(OptimizeImage::class)($this->record->icon);

            $this->record->update(['logo' => preg_replace('/^.*\/offices/', 'offices', $optimized)]);
        }
    }
}
