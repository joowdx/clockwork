<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Actions\ExportTimesheet;
use App\Models\Employee;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
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

        $this->closeModalByClickingAway(false);

        $this->form($this->exportForm());

        $this->action(fn (Collection $records, array $data) => $this->exportAction($records, $data));
    }

    public function exportConfirmation(): Htmlable
    {
        $html = <<<'HTML'
            <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                Note: Exporting in CSC format does not include employees with no timesheet for the selected period.
                You may need to generate their timesheets manually otherwise.
            </span>
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
                ->signature($data['electronic_signature'] ? user()?->signature : null)
                ->password($data['digital_signature'] ? $data['password'] : null)
                ->individual($data['individual'] ?? false)
                ->transmittal($data['transmittal'] ?? 0)
                ->grouping($data['grouping'] ?? false)
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
            Checkbox::make('electronic_signature')
                ->hintIcon('heroicon-o-check-badge')
                ->hintIconTooltip('Electronically sign the document. This does not provide security against tampering.')
                ->default(fn ($livewire) => $livewire->filters['electronic_signature'] ?? false)
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('digital_signature', $state ? $get('digital_signature') : false))
                ->rule(fn () => function ($attribute, $value, $fail) {
                    if ($value && ! user()?->signature) {
                        $fail('Configure your electronic signature first');
                    }
                }),
            Checkbox::make('digital_signature')
                ->hintIcon('heroicon-o-shield-check')
                ->hintIconTooltip('Digitally sign the document to prevent tampering.')
                ->dehydrated(true)
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('electronic_signature', $state ? true : $get('electronic_signature')))
                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                    if ($value && ! $get('electronic_signature')) {
                        $fail('Digital signature requires electronic signature');
                    }
                }),
            TextInput::make('password')
                ->password()
                ->visible(fn (Get $get) => $get('digital_signature') && $get('electronic_signature'))
                ->markAsRequired(fn (Get $get) => $get('digital_signature'))
                ->rule(fn (Get $get) => $get('digital_signature') ? 'required' : '')
                ->rule(fn () => function ($attribute, $value, $fail) {
                    if (! user()?->signature->verify($value)) {
                        $fail('The password is incorrect');
                    }
                }),
        ];

        $forms = [
            // Checkbox::make('individual')
            //     ->hintIcon('heroicon-o-question-mark-circle')
            //     ->hintIconTooltip('Export employee timesheet separately generating multiple files to be downloaded as an archive. However, this requires more processing time and to prevent server overload or request timeouts, please select no more than 25 records.')
            //     ->rule(fn (HasTable $livewire) => function ($attribute, $value, $fail) use ($livewire) {
            //         if ($value && count($livewire->selectedTableRecords) > 25) {
            //             $fail('Please select less than 25 records when exporting individually.');
            //         }
            //     }),
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
                ->simple(
                    DatePicker::make('date')
                        ->minDate(fn (Get $get) => $get('../../month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('../../month'))->endOfMonth())
                        ->markAsRequired()
                        ->rule('required')
                ),
            Group::make()
                ->columns($preview ? 1 : 2)
                ->schema([
                    Select::make('format')
                        ->live()
                        ->placeholder('Print format')
                        ->default(fn ($livewire) => $livewire->filters['format'] ?? 'csc')
                        ->required()
                        ->options(['default' => 'Default format', 'csc' => 'CSC format'])
                        ->hintIcon('heroicon-o-question-mark-circle')
                        ->hintIconTooltip('Employees with no timesheet data for the selected period are not included in the timesheet export when using the CSC format.'),
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
                ]),
            Group::make()
                ->columns(2)
                ->visible(! $preview)
                ->schema([
                    Select::make('transmittal')
                        ->live()
                        ->default(fn ($livewire) => $livewire->filters['transmittal'] ?? 0)
                        ->options([0, 1, 2, 3, 5])
                        ->in([0, 1, 2, 3, 5])
                        ->hintIcon('heroicon-o-question-mark-circle')
                        ->hintIconTooltip('Input the number of copies of transmittal to be generated.'),
                    Select::make('grouping')
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

        return $preview ? $forms : [...$forms, ...$config];
    }
}
