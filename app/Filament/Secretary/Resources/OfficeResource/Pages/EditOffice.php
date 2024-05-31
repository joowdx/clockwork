<?php

namespace App\Filament\Secretary\Resources\OfficeResource\Pages;

use App\Filament\Secretary\Resources\OfficeResource;
use App\Models\Deployment;
use Filament\Resources\Pages\EditRecord;

class EditOffice extends EditRecord
{
    protected static string $resource = OfficeResource::class;

    public function getContentTabLabel(): ?string
    {
        return 'Office Details';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function afterSave(): void
    {
        if (isset($this->record->employee_id)) {
            Deployment::query()
                ->where('employee_id', $this->record->employee_id)
                ->where('office_id', $this->record->id)
                ->update([
                    'active' => true,
                    'current' => true,
                    'supervisor_id' => null,
                ]);

            Deployment::query()
                ->where('employee_id', $this->record->employee_id)
                ->whereNot('office_id', $this->record->id)
                ->update(['supervisor_id' => null]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
