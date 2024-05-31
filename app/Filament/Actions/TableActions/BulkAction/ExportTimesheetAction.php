<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Actions\ExportTimesheet;
use App\Models\Employee;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Contracts\HasTable;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->icon('heroicon-o-clipboard-document-list');

        $this->modalHeading('Export');

        $this->modalDescription($this->exportConfirmation());

        $this->modalIcon('heroicon-o-document-arrow-down');

        $this->form($this->exportForm());

        $this->action(fn (Collection $records, array $data) => $this->exportAction($records, $data));
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

            return (new ExportTimesheet())
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

    protected function exportForm(): array
    {
        return [
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
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->type('month')
                ->required(),
            Select::make('period')
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
                ->disableOptionWhen(function (Get $get, ?string $value) {
                    if ($get('format') === 'csc') {
                        return false;
                    }

                    return match ($value) {
                        'full', '1st', '2nd', 'custom' => false,
                        default => true,
                    };
                })
                ->dehydrateStateUsing(function (Get $get, ?string $state) {
                    if ($state !== 'custom') {
                        return $state;
                    }

                    return $state.'|'.date('d', strtotime($get('from'))).'-'.date('d', strtotime($get('to')));
                })
                ->in(fn (Select $component): array => array_keys($component->getEnabledOptions())),
            DatePicker::make('from')
                ->label('Start')
                ->visible(fn (Get $get) => $get('period') === 'custom')
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m-d') : today()->subMonth()->startOfMonth()->format('Y-m-d'))
                ->validationAttribute('start')
                ->minDate(fn (Get $get) => $get('month').'-01')
                ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                ->required()
                ->dehydrated(false)
                ->beforeOrEqual('to'),
            DatePicker::make('to')
                ->label('End')
                ->visible(fn (Get $get) => $get('period') === 'custom')
                ->default(today()->day > 15 ? today()->endOfMonth()->format('Y-m-d') : today()->subMonth()->setDay(15)->format('Y-m-d'))
                ->validationAttribute('end')
                ->minDate(fn (Get $get) => $get('month').'-01')
                ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                ->required()
                ->dehydrated(false)
                ->afterOrEqual('from'),
            Select::make('format')
                ->live()
                ->placeholder('Print format')
                ->default('csc')
                ->required()
                ->options(['default' => 'Default format', 'csc' => 'CSC format']),
            Select::make('size')
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
                ->live()
                ->afterStateUpdated(fn ($get, $set, $state) => $set('digital_signature', $state ? $get('digital_signature') : false))
                ->rule(fn () => function ($attribute, $value, $fail) {
                    if ($value && ! auth()->user()->signature) {
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
                    if (! auth()->user()->signature->verify($value)) {
                        $fail('The password is incorrect');
                    }
                }),
        ];
    }
}
