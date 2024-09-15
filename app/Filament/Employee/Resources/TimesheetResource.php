<?php

namespace App\Filament\Employee\Resources;

use App\Enums\TimelogState;
use App\Filament\Actions\TableActions\ViewTimesheetAction;
use App\Filament\Employee\Resources\TimesheetResource\Pages;
use App\Jobs\ProcessTimetable;
use App\Models\Employee;
use App\Models\Timelog;
use App\Models\Timesheet;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;

    protected static ?string $navigationIcon = 'gmdi-document-scanner-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('timetables')->where('employee_id', Filament::auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->extraCellAttributes(['class' => 'font-mono'])
                    ->state(fn (Timesheet $record) => Carbon::parse($record->month)->format('M Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('days'),
                Tables\Columns\TextColumn::make('timetables_count')
                    ->label('Absences')
                    ->counts(['timetables' => fn ($query) => $query->where('absent', true)]),
                Tables\Columns\TextColumn::make('timetables_sum_undertime')
                    ->label('Undertime')
                    ->sum('timetables', 'undertime'),
                Tables\Columns\TextColumn::make('timetables_sum_overtime')
                    ->label('Overtime')
                    ->sum('timetables', 'overtime'),
                Tables\Columns\TextColumn::make('misses'),
            ])
            ->filters([

            ])
            ->actions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View')
                    ->slideOver(),
                Tables\Actions\Action::make('rectify')
                    ->icon('gmdi-border-color-o')
                    ->requiresConfirmation()
                    ->modalIcon('gmdi-border-color-o')
                    ->modalDescription('This will allow you to correct your timesheet by adjusting erroneous punch states.')
                    ->modalSubmitActionLabel('Save')
                    ->modalWidth('lg')
                    ->slideOver()
                    ->successNotificationTitle('Timesheet successfully rectified.')
                    ->failureNotificationTitle('Something went wrong while rectifying your timesheet.')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->minDate(fn (Timesheet $record) => Carbon::parse($record->month)->startOfMonth())
                            ->maxDate(fn (Timesheet $record) => Carbon::parse($record->month)->endOfMonth())
                            ->reactive()
                            ->dehydrated(false)
                            ->markAsRequired()
                            ->rule('required')
                            ->rule(fn () => function ($attribute, $value, $fail) {
                                if ($value === null) {
                                    return;
                                }

                                $employee = Employee::find(Filament::auth()->id());

                                if ($employee->timelogs()->whereDate('time', $value)->doesntExist()) {
                                    $fail('No data found for the selected date.');
                                }
                            })
                            ->afterStateUpdated(function ($component, $livewire, $set, $state) {
                                $livewire->validateOnly($component->getStatePath());

                                $timelogs = Employee::find(Filament::auth()->id())
                                    ->timelogs()
                                    ->with('scanner')
                                    ->whereDate('time', $state)
                                    ->reorder()
                                    ->orderBy('time')
                                    ->get();

                                $set('timelogs', $timelogs->map(function ($timelog) {
                                    return [
                                        'id' => $timelog->id,
                                        'scanner' => $timelog->scanner->name,
                                        'time' => Carbon::parse($timelog->time)->format('H:i'),
                                        'state' => $timelog->state,
                                        'recast' => $timelog->recast,
                                    ];
                                })->toArray());
                            }),
                        Forms\Components\Repeater::make('timelogs')
                            ->visible(fn (Get $get) => Employee::find(Filament::auth()->id())->timelogs()->whereDate('time', $get('date'))->exists())
                            ->label('Records')
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->itemLabel(function (array $state) {
                                $current = Timelog::find($state['id']);

                                $scanner = $state['scanner'];

                                if ($state['recast']) {
                                    $rectified = <<<HTML
                                    <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                                        {$scanner} ({$current->original->state->getLabel()})
                                    </span>
                                HTML;

                                    return str($rectified)->append($current->state !== $state['state'] ? '*' : '')->toHtmlString();
                                }

                                return $current->state === $state['state'] ? $scanner : "$scanner*";
                            })
                            ->columns(5)
                            ->required()
                            ->schema([
                                Forms\Components\TextInput::make('time')
                                    ->extraInputAttributes(['readonly' => true])
                                    ->dehydrated(false),
                                Forms\Components\Select::make('state')
                                    ->reactive()
                                    ->options(TimelogState::class)
                                    ->columnSpan(4)
                                    ->required()
                                    ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                                        if ($value === TimelogState::UNKNOWN) {
                                            $fail('Invalid state.');
                                        }

                                        if (in_array($value, [TimelogState::CHECK_IN_PM, TimelogState::CHECK_OUT_PM]) && $get('time') < '12:00') {
                                            $fail('Invalid state.');
                                        }
                                    }),
                            ]),
                    ])
                    ->action(function (Tables\Actions\Action $action, array $data) {
                        try {
                            DB::transaction(function () use ($data) {
                                $timelogs = collect($data['timelogs'])->mapWithKeys(fn ($timelog) => [$timelog['id'] => @$timelog['state']]);

                                $existing = Timelog::find(collect($data['timelogs'])->pluck('id'));

                                $existing->filter(fn ($timelog) => $timelog->state !== $timelogs[$timelog->id])->each(function ($timelog) use ($timelogs) {
                                    if ($timelog->recast) {
                                        if ($timelog->original->state !== $timelogs[$timelog->id]) {
                                            $timelog->forceFill(['state' => $timelogs[$timelog->id]])->save();
                                        } else {
                                            $timelog->original->forceFill(['masked' => false])->save();

                                            $timelog->delete();
                                        }
                                    } else {
                                        $data = [
                                            'time' => $timelog->time,
                                            'state' => $timelogs[$timelog->id],
                                            'mode' => $timelog->mode,
                                            'uid' => $timelog->uid,
                                            'device' => $timelog->device,
                                            'recast' => true,
                                        ];

                                        $timelog->forceFill(['masked' => true])->save();

                                        $timelog->revision()->make()->forceFill($data)->save();
                                    }

                                    ProcessTimetable::dispatchSync(Filament::auth()->user(), $timelog->time->clone());
                                });
                            });

                            $action->sendSuccessNotification();
                        } catch (Exception) {
                            $action->sendFailureNotification();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('This is a destructive action and will permanently delete this timesheet and all related timetables.')
                    ->form([
                        TextInput::make('password')
                            ->password()
                            ->currentPassword()
                            ->markAsRequired()
                            ->revealable()
                            ->rule('required'),
                        Checkbox::make('confirm')
                            ->label('I understand the consequences of this action')
                            ->markAsRequired()
                            ->accepted()
                            ->validationMessages(['accepted' => 'You must confirm that you understand the consequences of this action.']),
                    ]),
                // Tables\Actions\Action::make('update')
                //     ->requiresConfirmation()
                //     ->modalIcon('gmdi-edit-o')
                //     ->modalDescription(fn (Timesheet $record) => "Update " . Carbon::parse($record->month)->format('F Y') .  " timesheet information")
                //     ->form([
                //         Toggle::make('supervisor_field')
                //             ->label('Supervisor')
                //             ->reactive(),
                //         TextInput::make('supervisor')
                //             ->helperText('Direct supervisor\'s name')
                //             ->visible(fn (Get $get) => $get('supervisor_field')),
                //         TextInput::make('head')
                //             ->label('Department head')
                //             ->helperText('Department head\'s name'),
                //     ]),
            ])
            ->defaultSort('month', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
        ];
    }
}
