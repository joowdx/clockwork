<?php

namespace App\Filament\Employee\Resources\TimesheetResource\Pages;

use App\Filament\Actions\TableActions\BulkAction\GenerateTimesheetAction;
use App\Filament\Employee\Resources\TimesheetResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    public function getBreadcrumb(): ?string
    {
        return Filament::auth()->user()->titled_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->generate(),
        ];
    }

    protected function generate(): Action
    {
        $generate = app(GenerateTimesheetAction::class, ['name' => 'generate-timesheet']);

        return Action::make('generate')
            ->icon('heroicon-o-bolt')
            ->requiresConfirmation()
            ->modalIconColor('danger')
            ->modalDescription($generate->generateConfirmation())
            ->form([
                TextInput::make('month')
                    ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                    ->type('month')
                    ->required()
                    ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                        if (empty($get('password')) || empty($get('confirm'))) {
                            return;
                        }

                        /** @var Employee */
                        $employee = Filament::auth()->user();

                        @[$year, $month] = explode('-', $value);

                        if ($employee->timelogs()->whereMonth('time', $month)->whereYear('time', $year)->doesntExist()) {
                            return $fail('No data found for the selected month.');
                        }

                        if ($employee->timesheets()->whereMonth('month', $month)->whereYear('month', $year)->exists()) {
                            return $fail('Timesheet already exists for the selected month.');
                        }
                    }),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->markAsRequired()
                    ->rules([
                        'required',
                        fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                            if ($value === $get('month')) {
                                return;
                            }

                            if (! password_verify($value, Auth::user()->password)) {
                                $fail('The password is incorrect');
                            }
                        },
                    ]),
                Checkbox::make('confirm')
                    ->label('I understand what I am doing')
                    ->markAsRequired()
                    ->accepted()
                    ->validationMessages(['accepted' => 'You must confirm that you understand what you are doing.']),
            ])
            ->action(fn (array $data) => $generate->generateAction(Auth::user(), $data));
    }
}
