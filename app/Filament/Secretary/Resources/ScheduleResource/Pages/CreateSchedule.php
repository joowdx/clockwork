<?php

namespace App\Filament\Secretary\Resources\ScheduleResource\Pages;

use App\Enums\RequestStatus;
use App\Filament\Secretary\Resources\ScheduleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // $data['office_id'] =>

        return $data;
    }

    protected function afterCreate()
    {
        if (settings('requests')) {
            return;
        }

        $this->record->application()->create([
            'completed' => true,
            'status' => RequestStatus::APPROVE,
            'for' => 'approval',
            'remarks' => 'Schedule added directly without approval',
            'user_id' => Auth::id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('edit', ['record' => $this->getRecord(), ...$this->getRedirectUrlParameters()]);
    }
}
