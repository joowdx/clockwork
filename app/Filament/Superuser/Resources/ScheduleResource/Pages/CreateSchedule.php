<?php

namespace App\Filament\Superuser\Resources\ScheduleResource\Pages;

use App\Enums\RequestStatus;
use App\Filament\Superuser\Resources\ScheduleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function afterCreate()
    {
        $this->record->application()->create([
            'completed' => true,
            'status' => RequestStatus::APPROVE,
            'for' => 'approval',
            'remarks' => 'Schedule added directly with superuser privileges.',
            'user_id' => Auth::id(),
        ]);
    }
}
