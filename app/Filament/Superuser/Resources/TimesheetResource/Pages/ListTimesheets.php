<?php

namespace App\Filament\Superuser\Resources\TimesheetResource\Pages;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Enums\TimelogMode;
use App\Enums\TimelogState;
use App\Filament\Actions\ImportTimelogsAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTransmittalAction;
use App\Filament\Actions\TableActions\BulkAction\ViewTimesheetAction;
use App\Filament\Superuser\Resources\TimesheetResource;
use App\Jobs\ProcessTimesheet;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Office;
use App\Models\Timelog;
use App\Models\Timetable;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class ListTimesheets extends ListRecords
{
    use HasFiltersAction;

    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportTimelogsAction::make(),
            FilterAction::make()
                ->label('Option')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Option')
                ->slideOver(false)
                ->form([
                    Forms\Components\Select::make('model')
                        ->live()
                        ->placeholder('List to show')
                        ->default(Employee::class)
                        ->required()
                        ->options([Employee::class => 'Employee', Office::class => 'Office', Group::class => 'Group']),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ($this->filters['model'] ?? Employee::class)::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->limit(24)
                    ->getStateUsing(function (Employee $employee): string {
                        return str($employee->status?->value)
                            ->title()
                            ->when($employee->substatus?->value, function ($status) use ($employee) {
                                return $status->append(" ({$employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
                Tables\Columns\TextColumn::make('groups.name')
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('status')
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(EmploymentStatus::class)
                            ->placeholder('All')
                            ->multiple()
                            ->searchable(),
                        Forms\Components\Select::make('substatus')
                            ->visible(function (callable $get) {
                                $visibleOn = [
                                    EmploymentStatus::CONTRACTUAL->value,
                                ];

                                return count(array_diff($visibleOn, $get('status') ?? [])) < count($visibleOn);
                            })
                            ->options(EmploymentSubstatus::class)
                            ->placeholder('All')
                            ->multiple()
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['status'])) {
                            return;
                        }

                        $query->when(
                            in_array(EmploymentStatus::INTERNSHIP->value, $data['status']),
                            fn ($query) => $query->withoutGlobalScope('excludeInterns'),
                        );

                        $query->when(
                            $data['status'],
                            fn ($query) => $query->whereIn('status', $data['status'])
                        );

                        $query->when(
                            $data['substatus'],
                            fn ($query) => $query->whereIn('substatus', $data['substatus'])
                        );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (isset($data['status']) && count($data['status'])) {
                            $statuses = collect($data['status'])
                                ->map(fn ($status) => EmploymentStatus::tryFrom($status)?->getLabel());

                            $indicators[] = Indicator::make('Status: '.$statuses->join(', '))->removeField('status');
                        }

                        if (isset($data['substatus']) && count($data['substatus'])) {
                            $substatuses = collect($data['substatus'])
                                ->map(fn ($status) => EmploymentSubstatus::tryFrom($status)->getLabel());

                            $indicators[] = Indicator::make('Substatus: '.$substatuses->join(', '))->removeField('substatus');
                        }

                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('offices')
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->relationship('offices', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('groups')
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->extraAttributes(['class' => 'hidden'])
                        ->icon('heroicon-o-clipboard-document-list')
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalContent(fn ($record, $arguments) => $this->timesheetView($record, $arguments['data']['month']))
                        ->modalCancelActionLabel('Close')
                        ->modalFooterActionsAlignment('end'),
                    Tables\Actions\Action::make('timelogs')
                        ->icon('heroicon-o-newspaper'),
                    Tables\Actions\Action::make('timesheet')
                        ->icon('heroicon-o-clipboard-document-list')
                        // ->modalWidth('xl')
                        // ->modalSubmitAction(false)
                        // ->modalCancelActionLabel('Close')
                        // ->modalFooterActionsAlignment('end')
                        // ->modalContent(fn ($record, $data) => $this->timesheetView($record, Carbon::parse($data['month'])))
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('month')
                                ->markAsRequired()
                                ->rule('required')
                                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                                ->type('month'),
                        ])
                        ->action(fn ($data, $record) => $this->replaceMountedTableAction('view', $record->id, compact('data'))),
                ])
                    ->link()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-document-arrow-down')
                    ->modalIcon('heroicon-o-document-arrow-down')
                    ->modalDescription(fn (Employee $record) => "Export timesheet of {$record->name}")
                    ->visible(fn () => ($this->filters['model'] ?? Employee::class) === Employee::class)
                    ->form(fn () => $this->exportForm())
                    ->action(fn (Employee $record, array $data) => $this->exportAction($record, $data)),
                Tables\Actions\Action::make('generate')
                    ->icon('heroicon-o-bolt')
                    ->requiresConfirmation()
                    ->modalDescription($this->generateConfirmation())
                    ->successNotificationTitle(fn ($record) => "Timesheet for {$record->name} generated.")
                    ->form($this->generateForm())
                    ->action(function (Employee $record, Tables\Actions\Action $component, array $data) {
                        if (! empty($data) && $data['month'] === $data['password']) {
                            $this->replaceMountedTableAction('thaumaturge', $record->id, ['month' => $data['month']]);

                            return;
                        }

                        ProcessTimesheet::dispatchSync($record, $data['month']);

                        $component->sendSuccessNotification();
                    }),
                Tables\Actions\Action::make('thaumaturge')
                    ->extraAttributes(['class' => 'hidden'])
                    ->modalHeading(fn ($record) => $record->name)
                    ->modalAlignment('center')
                    ->icon('heroicon-o-puzzle-piece')
                    ->modalIcon('heroicon-o-puzzle-piece')
                    ->modalDescription(null)
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->successNotificationTitle(fn ($record) => "Timesheet for {$record->name} generated.")
                    ->form(function ($arguments) {
                        $month = Carbon::parse($arguments['month']);

                        $from = $month->clone()->startOfMonth();

                        $to = $month->clone()->endOfMonth();

                        return [
                            Forms\Components\Tabs::make()
                                ->contained(false)
                                ->tabs([
                                    Forms\Components\Tabs\Tab::make('New')
                                        ->schema([
                                            Forms\Components\Repeater::make('timelogs')
                                                ->hiddenLabel()
                                                ->collapsible()
                                                ->cloneable()
                                                ->reorderable(false)
                                                ->columns(2)
                                                ->defaultItems(0)
                                                ->addActionLabel('Create')
                                                ->itemLabel(function (array $state) {
                                                    if (is_null($state['time'])) {
                                                        return null;
                                                    }

                                                    return Carbon::parse($state['time'])->format('Y-m-d H:i:s').' '.$state['state']->getLabel();
                                                })
                                                ->schema(function (Employee $record) use ($from, $to) {
                                                    $scanners = $record->scanners()->whereNotNull('scanners.uid')->get();

                                                    return [
                                                        Forms\Components\Select::make('device')
                                                            ->live()
                                                            ->options($scanners->pluck('name', 'uid')->toArray())
                                                            ->required()
                                                            ->afterStateUpdated(function (int $state, Forms\Set $set) use ($scanners) {
                                                                $set('uid', $scanners->first(fn ($scanner) => $scanner->uid === $state)?->pivot->uid);
                                                            }),
                                                        Forms\Components\DateTimePicker::make('time')
                                                            ->live()
                                                            ->distinct()
                                                            ->required()
                                                            ->minDate($from->format('Y-m-d H:i:s'))
                                                            ->maxDate($to->format('Y-m-d H:i:s')),
                                                        Forms\Components\Select::make('state')
                                                            ->live()
                                                            ->options(TimelogState::class)
                                                            ->default(TimelogState::CHECK_IN)
                                                            ->required(),
                                                        Forms\Components\Select::make('mode')
                                                            ->live()
                                                            ->options(function () {
                                                                return collect(TimelogMode::cases())->mapWithKeys(fn ($mode) => [
                                                                    $mode->value => $mode->getLabel(true),
                                                                ]);
                                                            })
                                                            ->default(TimelogMode::FINGERPRINT_1)
                                                            ->required(),
                                                        Forms\Components\Hidden::make('pseudo')
                                                            ->default(true),
                                                        Forms\Components\Hidden::make('uid')
                                                            ->default(null),
                                                    ];
                                                }),
                                        ]),
                                ]),
                        ];
                    })
                    ->action(function (Employee $record, Tables\Actions\Action $component, array $data) {
                        Timelog::upsert($data['timelogs'], ['time', 'device', 'uid', 'mode', 'state'], ['uid', 'time', 'state', 'mode']);

                        ProcessTimesheet::dispatchSync($record, Carbon::parse($this->filters['month'] ?? today()->startOfMonth()));

                        $component->sendSuccessNotification();
                    }),
            ])
            ->bulkActions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View'),
                Tables\Actions\BulkActionGroup::make([
                    ExportTimesheetAction::make()
                        ->label('Timesheet'),
                    ExportTransmittalAction::make()
                        ->label('Transmittal'),
                ])
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down'),
                Tables\Actions\BulkAction::make('generate')
                    ->icon('heroicon-o-bolt')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalIconColor('danger')
                    ->modalDescription($this->generateConfirmation())
                    ->form($this->generateForm())
                    ->action(fn (Collection $records, array $data) => $this->generateAction($records, $data)),
            ])
            ->deselectAllRecordsWhenFiltered(false)
            ->recordAction(null);
    }

    public function timelogsView(Employee $employee, Carbon|string $month): Htmlable
    {

        return str()->toHtmlString();
    }

    protected function timesheetView(Employee $employee, Carbon|string $month): Htmlable
    {
        $month = Carbon::parse($month);

        $body = $employee->timesheets()
            ->month($month)
            ->first()
            ?->timetables()
            ->get()
            ->map(function (Timetable $timetable) {
                $p1 = substr($time = $timetable->punch['p1']['time'] ?? '', 0, strrpos($time, ':'));
                $p2 = substr($time = $timetable->punch['p2']['time'] ?? '', 0, strrpos($time, ':'));
                $p3 = substr($time = $timetable->punch['p3']['time'] ?? '', 0, strrpos($time, ':'));
                $p4 = substr($time = $timetable->punch['p4']['time'] ?? '', 0, strrpos($time, ':'));

                $hf = $timetable->half ? '✔' : '';
                $ok = $timetable->invalid ? '✖' : '';

                return <<<HTML
                    <tr class="border-t" style="border-color:#80808080!important;">
                        <td class="font-mono" style="padding:0 0.75em;">{$timetable->date->format('m-d D')}</td>
                        <td class="font-mono text-left" style="padding:0 0.75em;">{$ok}</td>
                        <td class="font-mono text-left" style="padding:0 0.75em;">{$hf}</td>
                        <td class="font-mono" style="padding:0 0.75em;">{$p1}</td>
                        <td class="font-mono" style="padding:0 0.75em;">{$p2}</td>
                        <td class="font-mono" style="padding:0 0.75em;">{$p3}</td>
                        <td class="font-mono" style="padding:0 0.75em;">{$p4}</td>
                        <td class="font-mono text-right" style="padding:0 0.75em;">{$timetable->undertime}</td>
                        <td class="font-mono text-right" style="padding:0 0.75em;">{$timetable->overtime}</td>
                        <td class="font-mono text-right" style="padding:0 0.75em;">{$timetable->duration}</td>
                    </tr>
                HTML;
            })->join('');

        $html = <<<HTML
            <div class="font-sans">
                <div class="py-3">
                    <h2 class="text-base font-semibold leading-6 fi-modal-heading text-gray-950 dark:text-white">
                        {$employee->name}
                    </h2>
                    <h3 class="text-sm font-semibold leading-6 fi-modal-heading text-gray-950 dark:text-white">
                        {$month->format('F Y')}
                    </h3>
                </div>
                <hr>
                <table class="w-full text-sm table-fixed fi-ta-table">
                    <thead>
                        <tr>
                            <th class="text-left" style="padding-left:1em;padding:0 0.75em;"> Date </th>
                            <th class="text-left" style="width:5%;padding:0 0.75em;"> OK </th>
                            <th class="text-left" style="width:5%;padding:0 0.75em;"> HF </th>
                            <th class="text-left" style="width:10%;padding:0 0.75em;"> P1 </th>
                            <th class="text-left" style="width:10%;padding:0 0.75em;"> P2 </th>
                            <th class="text-left" style="width:10%;padding:0 0.75em;"> P3 </th>
                            <th class="text-left" style="width:10%;padding:0 0.75em;"> P4 </th>
                            <th class="text-right" style="width:10%;padding:0 0.75em;"> UT </th>
                            <th class="text-right" style="width:10%;padding:0 0.75em;"> OT </th>
                            <th class="text-right" style="width:10%;padding:0 0.75em;"> ΣT </th>
                        </tr>
                    </thead>
                    <tbody>
                        {$body}
                    </tbody>
                </table>
            </div>
        HTML;

        return str($html)
            ->toHtmlString();
    }

    protected function generateConfirmation(): string
    {
        return 'Timesheets are automatically generated.
        Only do this when you know what you are doing as this will overwrite the existing timesheet data.
        To proceed, please enter your password.';
    }

    protected function generateForm(): array
    {
        return [
            Forms\Components\TextInput::make('month')
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->type('month')
                ->required(),
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->markAsRequired()
                ->rules([
                    'required',
                    fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                        if ($value === $get('month')) {
                            return;
                        }

                        if (! password_verify($value, auth()->user()->password)) {
                            $fail('The password is incorrect');
                        }
                    },
                ]),
        ];
    }

    protected function generateAction(Collection|Employee $employee, array $data): void
    {
        if ($employee instanceof Employee || $employee->count() === 1) {
            $employee = $employee instanceof Collection ? $employee->first() : $employee;

            ProcessTimesheet::dispatchSync($employee, $data['month']);

            Notification::make()
                ->success()
                ->title("Timesheet for {$employee->name} generated.")
                ->send();

            return;
        }

        $jobs = $employee->map(function (Employee $employee) use ($data) {
            return new ProcessTimesheet($employee, $data['month']);
        });

        $employee->ensure(Employee::class);

        Notification::make()
            ->info()
            ->title('Timesheet generation will start shortly')
            ->send();

        $user = auth()->user();

        Bus::batch($jobs->all())
            ->then(function () use ($data, $employee, $user) {
                $names = $employee->pluck('name')->sort();

                Notification::make()
                    ->info()
                    ->title('Timesheets are being generated')
                    ->body("<b>({$data['month']})</b> <br> To be generated for (please wait for a while): <br>{$names->join('<br>')}")
                    ->sendToDatabase($user);
            })
            ->catch(function () use ($user) {
                Notification::make()
                    ->error()
                    ->title('Failed to generate timesheets')
                    ->body('Please try again')
                    ->sendToDatabase($user);
            })
            ->onQueue('main')
            ->dispatch();
    }
}
