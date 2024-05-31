<?php

namespace App\Filament\Superuser\Resources\TimesheetResource\Pages;

use App\Actions\ExportTimesheet;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Enums\TimelogMode;
use App\Enums\TimelogState;
use App\Filament\Actions\ImportTimelogsAction;
use App\Filament\Superuser\Resources\TimesheetResource;
use App\Jobs\ProcessTimesheet;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Office;
use App\Models\Timelog;
use App\Models\Timetable;
use Exception;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('timesheet')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-clipboard-document-list')
                        ->modalHeading('Export')
                        ->modalDescription($this->exportConfirmation())
                        ->modalIcon('heroicon-o-document-arrow-down')
                        ->form($this->exportForm(bulk: true))
                        ->action(fn (Collection $records, array $data) => $this->exportAction($records, $data)),
                    Tables\Actions\BulkAction::make('transmittal')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->disabled(fn () => isset($this->filters['transmittal']) && $this->filters['transmittal']),
                ])
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(true),
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

    protected function exportConfirmation(): Htmlable
    {
        $html = <<<'HTML'
            <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                Note: Exporting in CSC format does not include employees with no timesheet for the selected period.
                You may need to generate their timesheets manually otherwise.
            </span>
        HTML;

        return str($html)->toHtmlString();
    }

    protected function generateConfirmation(): string
    {
        return 'Timesheets are automatically generated.
        Only do this when you know what you are doing as this will overwrite the existing timesheet data.
        To proceed, please enter your password.';
    }

    protected function exportForm(bool $bulk = false): array
    {
        return [
            Forms\Components\Checkbox::make('individual')
                ->visible($bulk)
                ->hintIcon('heroicon-o-question-mark-circle')
                ->hintIconTooltip('Export employee timesheet separately generating multiple files to be downloaded as an archive. However, this requires more processing time and to prevent server overload or request timeouts, please select no more than 25 records.')
                ->rule(fn (HasTable $livewire) => function ($attribute, $value, $fail) use ($livewire) {
                    if ($value && count($livewire->selectedTableRecords) > 25) {
                        $fail('Please select less than 25 records when exporting individually.');
                    }
                }),
            Forms\Components\TextInput::make('month')
                ->live()
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->type('month')
                ->required(),
            Forms\Components\Select::make('period')
                ->default(today()->day > 15 ? '1st' : 'full')
                ->required()
                ->live()
                ->options([
                    'full' => 'Full month',
                    '1st' => 'First half',
                    '2nd' => 'Second half',
                    'regular' => 'Regular days',
                    'overtime' => 'Overtime work',
                    'custom' => 'Custom range',
                ])
                ->disableOptionWhen(function (Forms\Get $get, ?string $value) {
                    if ($get('format') === 'csc') {
                        return false;
                    }

                    return match ($value) {
                        'full', '1st', '2nd', 'custom' => false,
                        default => true,
                    };
                })
                ->dehydrateStateUsing(function (Forms\Get $get, ?string $state) {
                    if ($state !== 'custom') {
                        return $state;
                    }

                    return $state.'|'.date('d', strtotime($get('from'))).'-'.date('d', strtotime($get('to')));
                })
                ->in(fn (Forms\Components\Select $component): array => array_keys($component->getEnabledOptions())),
            Forms\Components\DatePicker::make('from')
                ->label('Start')
                ->visible(fn (Forms\Get $get) => $get('period') === 'custom')
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m-d') : today()->subMonth()->startOfMonth()->format('Y-m-d'))
                ->validationAttribute('start')
                ->minDate(fn (Forms\Get $get) => $get('month').'-01')
                ->maxDate(fn (Forms\Get $get) => Carbon::parse($get('month'))->endOfMonth())
                ->required()
                ->dehydrated(false)
                ->beforeOrEqual('to'),
            Forms\Components\DatePicker::make('to')
                ->label('End')
                ->visible(fn (Forms\Get $get) => $get('period') === 'custom')
                ->default(today()->day > 15 ? today()->endOfMonth()->format('Y-m-d') : today()->subMonth()->setDay(15)->format('Y-m-d'))
                ->validationAttribute('end')
                ->minDate(fn (Forms\Get $get) => $get('month').'-01')
                ->maxDate(fn (Forms\Get $get) => Carbon::parse($get('month'))->endOfMonth())
                ->required()
                ->dehydrated(false)
                ->afterOrEqual('from'),
            Forms\Components\Select::make('format')
                ->live()
                ->placeholder('Print format')
                ->default('csc')
                ->required()
                ->options(['default' => 'Default format', 'csc' => 'CSC format']),
            Forms\Components\Select::make('size')
                ->live()
                ->placeholder('Paper Size')
                ->default('folio')
                ->required()
                ->options([
                    'a4' => 'A4 (210mm x 297mm)',
                    'letter' => 'Letter (216mm x 279mm)',
                    'folio' => 'Folio (216mm x 330mm)',
                    'legal' => 'Legal (216mm x 356mm)',
                ]),
            // Forms\Components\Select::make('transmittal')
            //     ->visible($bulk)
            //     ->live()
            //     ->default(false)
            //     ->boolean()
            //     ->required()
            //     ->placeholder('Generate transmittal'),
            Forms\Components\Checkbox::make('electronic_signature')
                ->hintIcon('heroicon-o-check-badge')
                ->hintIconTooltip('Electronically sign the document for quick and convenient validation. This does not provide security against tampering.')
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('digital_signature', $state ? $get('digital_signature') : false))
                ->rule(fn () => function ($attribute, $value, $fail) {
                    if ($value && ! auth()->user()->signature) {
                        $fail('Configure your electronic signature first');
                    }
                }),
            Forms\Components\Checkbox::make('digital_signature')
                ->hintIcon('heroicon-o-shield-check')
                ->hintIconTooltip('Digitally sign the document to prevent tampering.')
                ->dehydrated(true)
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('electronic_signature', $state ? true : $get('electronic_signature')))
                ->rule(fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                    if ($value && ! $get('electronic_signature')) {
                        $fail('Digital signature requires electronic signature');
                    }
                }),
            Forms\Components\TextInput::make('password')
                ->password()
                ->visible(fn (Forms\Get $get) => $get('digital_signature') && $get('electronic_signature'))
                ->markAsRequired(fn (Forms\Get $get) => $get('digital_signature'))
                ->rule(fn (Forms\Get $get) => $get('digital_signature') ? 'required' : '')
                ->rule(fn () => function ($attribute, $value, $fail) {
                    if (! auth()->user()->signature->verify($value)) {
                        $fail('The password is incorrect');
                    }
                }),
        ];
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

    protected function exportAction(Collection|Employee $employee, array $data): StreamedResponse|BinaryFileResponse|Notification
    {
        $actionException = new class extends Exception
        {
            public function __construct(public readonly ?string $title = null, public readonly ?string $body = null)
            {
                parent::__construct();
            }
        };

        try {
            if ($employee instanceof Collection && $employee->count() > 100) {
                throw new $actionException('Too many records', 'To prevent server overload, please select less than 100 records');
            }

            return (new ExportTimesheet)
                ->employee($employee)
                ->month($data['month'])
                ->period($data['period'])
                ->format($data['format'])
                ->size($data['size'])
                ->signature($data['electronic_signature'] ? auth()->user()->signature : null)
                ->password($data['digital_signature'] ? $data['password'] : null)
                ->individual($data['individual'] ?? false)
                ->download();
        } catch (ProcessFailedException $exception) {
            $message = $employee instanceof Collection ? 'Failed to export timesheets' : "Failed to export {$employee->name}'s timesheet";

            return Notification::make()
                ->danger()
                ->title($message)
                ->body('Please try again later')
                ->send();
        } catch (Exception $exception) {
            if ($exception instanceof $actionException) {
                return Notification::make()
                    ->danger()
                    ->title($exception->title)
                    ->body($exception->body)
                    ->send();
            }

            throw $exception;
        }
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
