<?php

namespace App\Filament\Superuser\Resources\OfficeResource\Pages;

use App\Filament\Superuser\Resources\OfficeResource;
use App\Models\Deployment;
use Filament\Actions;
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
            Deployment::upsert([
                'employee_id' => $this->record->employee_id,
                'office_id' => $this->record->id,
                'active' => true,
                'current' => true,
                'supervisor_id' => null,
            ], [
                'employee_id',
                'office_id',
            ], [
                'active',
                'current',
                'supervisor_id',
            ]);

            Deployment::query()
                ->where('employee_id', $this->record->employee_id)
                ->whereNot('office_id', $this->record->id)
                ->update([
                    'current' => false,
                    'supervisor_id' => null,
                ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
