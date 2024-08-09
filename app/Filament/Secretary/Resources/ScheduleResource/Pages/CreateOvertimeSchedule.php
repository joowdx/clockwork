<?php

namespace App\Filament\Secretary\Resources\ScheduleResource\Pages;

use App\Enums\RequestStatus;
use App\Filament\Secretary\Resources\ScheduleResource;
use App\Models\Schedule;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;

class CreateOvertimeSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected static ?string $title = 'Create Overtime Schedule';

    protected static ?string $breadcrumb = 'Create Overtime';

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->disabled(fn (?Schedule $record) => ! in_array($record?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN]))
                ->schema([
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
