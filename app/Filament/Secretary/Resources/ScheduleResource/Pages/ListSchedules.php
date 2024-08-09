<?php

namespace App\Filament\Secretary\Resources\ScheduleResource\Pages;

use App\Filament\Secretary\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\CreateAction::make(),
                // Actions\Action::make('new_overtime_schedule')
                //     ->icon('heroicon-m-plus')
                //     ->url(route('filament.secretary.resources.schedules.create-overtime')),
            ]),
        ];
    }
}
