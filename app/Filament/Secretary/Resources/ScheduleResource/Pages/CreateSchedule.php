<?php

namespace App\Filament\Secretary\Resources\ScheduleResource\Pages;

use App\Filament\Secretary\Resources\ScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // $data['office_id'] =>

        return $data;
    }
}
