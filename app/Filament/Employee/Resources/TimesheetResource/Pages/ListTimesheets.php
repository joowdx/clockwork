<?php

namespace App\Filament\Employee\Resources\TimesheetResource\Pages;

use App\Enums\TimelogState;
use App\Filament\Employee\Resources\TimesheetResource;
use App\Jobs\ProcessTimetable;
use App\Models\Employee;
use App\Models\Timelog;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            $this->rectify(),
        ];
    }

    protected function rectify(): Action
    {
        return Action::make('rectify')
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
                DatePicker::make('date')
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
                Repeater::make('timelogs')
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
                        TextInput::make('time')
                            ->extraInputAttributes(['readonly' => true])
                            ->dehydrated(false),
                        Select::make('state')
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
            ->action(function (Action $action, array $data) {
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
            });
    }
}
