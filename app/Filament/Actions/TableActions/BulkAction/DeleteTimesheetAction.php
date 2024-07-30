<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Models\Timesheet;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class DeleteTimesheetAction extends BulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name ??= 'delete-timesheets';

        $this->requiresConfirmation();

        $this->icon('heroicon-o-trash');

        $this->groupedIcon('heroicon-o-trash');

        $this->modalIcon('heroicon-o-trash');

        $this->modalDescription('This will permanently delete the selected employee\'s timesheets for the selected month.');

        $this->color('danger');

        $this->form([
            TextInput::make('month')
                ->markAsRequired()
                ->rule('required')
                ->type('month')
                ->live(),
            TextInput::make('password')
                ->markAsRequired()
                ->rule('required')
                ->currentPassword()
                ->type('password')
                ->live(),
            TextInput::make('confirmation')
                ->markAsRequired()
                ->rule('required')
                ->visible(fn (Get $get) => $get('month') && $get('password'))
                ->helperText(fn (Get $get) => $get('month') ? 'Please type "DELETE ' . $get('month') . '" to confirm.' : '')
                ->rule(fn (Get $get) => function ($a, $v, $f) use ($get) {
                    if ($v !== 'DELETE ' . $get('month')) {
                        $f('To confirm, please type "DELETE ' . $get('month') . '".');
                    }

                }),
            Checkbox::make('acknowledgement')
                ->helperText('I understand that this action is destructive, irreversible and still want to proceed.')
                ->rule('accepted'),
        ]);

        $this->action(function (Collection $records, array $data) {
            Timesheet::query()
                ->whereIn('employee_id', $records->pluck('id'))
                ->whereDate('month', $data['month'] . '-01')
                ->delete();

            Notification::make()
                ->danger()
                ->title('Timesheets for the selected employees of the month ' . $data['month'] . ' have been deleted.')
                ->send();
        });
    }
}
