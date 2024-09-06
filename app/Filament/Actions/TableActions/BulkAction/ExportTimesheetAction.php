<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Actions\ExportTimesheet;
use App\Models\Employee;
use App\Models\User;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExportTimesheetAction extends BulkAction
{
    public static function make(?string $name = null): static
    {
        $class = static::class;

        $name ??= 'export-timesheet';

        $static = app($class, ['name' => $name]);

        $static->configure();

        return $static;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->icon('heroicon-o-clipboard-document-list');

        $this->modalHeading('Export Timesheets');

        $this->modalDescription($this->exportConfirmation());

        $this->modalIcon('heroicon-o-document-arrow-down');

        $this->modalWidth('lg');

        $this->slideOver();

        $this->closeModalByClickingAway(false);

        $this->form($this->exportForm());

        $this->action(fn (Collection $records, array $data) => $this->exportAction($records, $data));
    }

    public function exportConfirmation(): Htmlable
    {
        $html = <<<'HTML'
            <!-- <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                Note: Exporting in CSC format does not include employees with no timesheet for the selected period.
                You may need to generate their timesheets manually otherwise.
            </span> -->
        HTML;

        return str($html)->toHtmlString();
    }

    public function exportAction(Collection|Employee $employee, array $data): StreamedResponse|BinaryFileResponse|Notification
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
                ->dates($data['dates'] ?? [])
                ->format($data['format'])
                ->size($data['size'])
                ->user($data['user'] ? User::find($data['user']) : user())
                ->signature($data['electronic_signature'])
                ->password($data['digital_signature'] ? $data['password'] : null)
                ->individual($data['individual'] ?? false)
                ->transmittal($data['transmittal'] ?? 0)
                ->grouping($data['grouping'] ?? false)
                ->misc([
                    'weekends' => $data['weekends'],
                    'holidays' => $data['holidays'],
                    'highlights' => $data['highlights'],
                ])
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

    public function exportForm(bool $preview = false): array
    {
        $config = [
            Select::make('size')
                ->visible($preview)
                ->live()
                ->placeholder('Paper Size')
                ->default(fn ($livewire) => $livewire->filters['folio'] ?? 'folio')
                ->required()
                ->options([
                    'a4' => 'A4 (210mm x 297mm)',
                    'letter' => 'Letter (216mm x 279mm)',
                    'folio' => 'Folio (216mm x 330mm)',
                    'legal' => 'Legal (216mm x 356mm)',
                ]),
            // Select::make('transmittal')
            //     ->visible($bulk)
            //     ->live()
            //     ->default(false)
            //     ->boolean()
            //     ->required()
            //     ->placeholder('Generate transmittal'),
            Select::make('user')
                ->label('Spoof as')
                ->visible(fn () => ($user = user())->developer && $user->superuser)
                ->reactive()
                ->options(User::take(25)->whereNot('id', Auth::id())->orderBy('name')->pluck('name', 'id'))
                ->getSearchResultsUsing(fn ($search) => User::take(25)->whereNot('id', Auth::id())->where('name', 'ilike', "%{$search}%")->pluck('name', 'id'))
                ->searchable(),
            Checkbox::make('electronic_signature')
                ->helperText('Electronically sign the document. This does not provide security against tampering.')
                ->default(fn ($livewire) => $livewire->filters['electronic_signature'] ?? false)
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('digital_signature', $state ? $get('digital_signature') : false))
                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                    $user = $get('user') ? User::find($get('user')) : user();

                    if ($value && ! $user->signature) {
                        $fail('Configure your electronic signature first');
                    }
                }),
            Checkbox::make('digital_signature')
                ->helperText('Digitally sign the document to prevent tampering.')
                ->dehydrated(true)
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('electronic_signature', $state ? true : $get('electronic_signature')))
                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                    if (! $value) {
                        return;
                    }

                    if (! $get('electronic_signature')) {
                        $fail('Digital signature requires electronic signature');
                    }

                    $user = $get('user') ? User::find($get('user')) : user();

                    if ($user->signature?->certificate === null) {
                        $name = $get('user')
                            ? str("$user->name'")->when(! str($user->name)->endsWith('s'), fn ($str) => $str->append('s'))->toString()
                            : 'your';

                        return $fail('Please configure '.($get('user') ? $name : 'your').' digital signature certificate first');
                    }
                }),
            TextInput::make('password')
                ->password()
                ->visible(fn (Get $get) => $get('digital_signature') && $get('electronic_signature') && ($get('user') ? User::find($get('user')) : user())->signature->certificate)
                ->markAsRequired(fn (Get $get) => $get('digital_signature'))
                ->rule(fn (Get $get) => $get('digital_signature') ? 'required' : '')
                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                    $user = $get('user') ? User::find($get('user')) : user();

                    if ($user->signature->certificate !== null && ! $user?->signature->verify($value)) {
                        $fail('The password is incorrect');
                    }
                }),
        ];

        $period = [
            // Checkbox::make('individual')
            //     ->hintIcon('heroicon-o-question-mark-circle')
            //     ->hintIconTooltip('Export employee timesheet separately generating multiple files to be downloaded as an archive. However, this requires more processing time and to prevent server overload or request timeouts, please select no more than 25 records.')
            //     ->rule(fn (HasTable $livewire) => function ($attribute, $value, $fail) use ($livewire) {
            //         if ($value && count($livewire->selectedTableRecords) > 25) {
            //             $fail('Please select less than 25 records when exporting individually.');
            //         }
            //     }),
            Radio::make('format')
                ->live()
                // ->placeholder('Print format')
                ->default(fn ($livewire) => $livewire->filters['format'] ?? 'default')
                ->required()
                ->options([
                    'default' => 'Default format',
                    'csc' => 'CSC format',
                    'preformatted' => 'CSC format (preformatted)',
                ])
                ->descriptions([
                    'default' => 'Raw attendance data. Displays more detailed information about the employees\' attendance.',
                    'csc' => 'Export in CSC form with attendance data based off the employees\' set schedules. You may need to generate their timesheets manually if they have no timesheet data for the selected period. Ignores employees with no timesheet data.',
                    'preformatted' => 'Schedule-agnostic attendance preformatted in CSC form. Attendance data are fetched as is and may not reflect the actual schedule of the employees.',
                ]),
            TextInput::make('month')
                ->live()
                ->default(fn ($livewire) => $livewire->filters['month'] ?? (today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m')))
                ->type('month')
                ->required(),
            Select::make('period')
                ->default(fn ($livewire) => $livewire->filters['period'] ?? (today()->day > 15 ? '1st' : 'full'))
                ->required()
                ->live()
                ->options([
                    'full' => 'Full month',
                    '1st' => 'First half',
                    '2nd' => 'Second half',
                    'regular' => 'Regular days',
                    'overtime' => 'Overtime work',
                    'dates' => 'Custom dates',
                    'range' => 'Custom range',
                ])
                ->disableOptionWhen(function (Get $get, ?string $value) {
                    if ($get('format') === 'csc') {
                        return false;
                    }

                    if ($get('format') === 'preformatted') {
                        return ! in_array($value, ['full', '1st', '2nd', 'dates', 'range']);
                    }

                    return match ($value) {
                        'full', '1st', '2nd', 'dates', 'range' => false,
                        default => true,
                    };
                })
                ->dehydrateStateUsing(function (Get $get, ?string $state) {
                    if ($state !== 'range') {
                        return $state;
                    }

                    return $state.'|'.date('d', strtotime($get('from'))).'-'.date('d', strtotime($get('to')));
                })
                ->in(fn (Select $component): array => array_keys($component->getEnabledOptions())),
            Group::make()
                ->columns(2)
                ->visible(fn (Get $get) => $get('period') === 'range')
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('from')
                        ->label('Start')
                        ->default(fn ($livewire) => $livewire->filters['from'] ?? (today()->day > 15 ? today()->startOfMonth()->format('Y-m-d') : today()->subMonth()->startOfMonth()->format('Y-m-d')))
                        ->validationAttribute('start')
                        ->minDate(fn (Get $get) => $get('month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                        ->markAsRequired()
                        ->rule('required')
                        ->dehydrated(false)
                        ->beforeOrEqual('to'),
                    DatePicker::make('to')
                        ->label('End')
                        ->default(fn ($livewire) => $livewire->filters['to'] ?? (today()->day > 15 ? today()->endOfMonth()->format('Y-m-d') : today()->subMonth()->setDay(15)->format('Y-m-d')))
                        ->validationAttribute('end')
                        ->minDate(fn (Get $get) => $get('month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                        ->markAsRequired()
                        ->rule('required')
                        ->dehydrated(false)
                        ->afterOrEqual('from'),
                ]),
            Repeater::make('dates')
                ->visible(fn (Get $get) => $get('period') === 'dates')
                ->default(fn ($livewire) => $livewire->filters['dates'] ?? [])
                ->required()
                ->reorderable(false)
                ->addActionLabel('Add a date')
                ->grid(2)
                ->columnSpanFull()
                ->simple(
                    DatePicker::make('date')
                        ->minDate(fn (Get $get) => $get('../../month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('../../month'))->endOfMonth())
                        ->markAsRequired()
                        ->rule('required')
                ),
        ];
        $export = [
            Select::make('size')
                ->visible(! $preview)
                ->live()
                ->placeholder('Paper Size')
                ->default(fn ($livewire) => $livewire->filters['folio'] ?? 'folio')
                ->required()
                ->options([
                    'a4' => 'A4',
                    'letter' => 'Letter',
                    'folio' => 'Folio',
                    'legal' => 'Legal',
                ]),
            Group::make()
                ->columns(2)
                ->schema([
                    Select::make('transmittal')
                        ->live()
                        ->visible(! $preview)
                        ->default(fn ($livewire) => $livewire->filters['transmittal'] ?? 0)
                        ->options([0, 1, 2, 3, 5])
                        ->in([0, 1, 2, 3, 5])
                        ->hintIcon('heroicon-o-question-mark-circle')
                        ->hintIconTooltip('Input the number of copies of transmittal to be generated.'),
                    Select::make('grouping')
                        ->visible(! $preview)
                        ->disabled(fn (Get $get) => $get('transmittal') <= 0)
                        ->default(fn ($livewire) => $livewire->filters['grouping'] ?? 'offices')
                        ->options([
                            'offices' => 'Office',
                            false => 'None',
                        ])
                        ->hintIcon('heroicon-o-question-mark-circle')
                        ->hintIconTooltip('
                            Grouping by office might generate multiple timesheets for employees with multiple offices.
                            No grouping will generate a single transmittal for all selected employees.
                        '),
                ]),
        ];

        return $preview
            ? [...$period, ...$export]
            : [
                Tabs::make()->contained(false)->tabs([
                    Tab::make('Timesheet')
                        ->schema($period),
                    Tab::make('Options')
                        ->schema([...$export, ...$config]),
                    Tab::make('Miscellaneous')
                        ->visible(fn (Get $get) => in_array($get('format'), ['csc', 'preformatted']))
                        ->schema([
                            Checkbox::make('weekends')
                                ->default(true)
                                ->helperText('Label weekends in the timesheet if no attendance data is present.'),
                            Checkbox::make('holidays')
                                ->default(true)
                                ->helperText('Label holidays in the timesheet if no attendance data is present.'),
                            Checkbox::make('highlights')
                                ->default(true)
                                ->helperText('Highlight blank or empty entries in the timesheet.'),
                        ]),
                ]),
            ];
    }
}
