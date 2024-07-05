<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Actions\ExportAttendance;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Enums\TimelogMode;
use App\Enums\TimelogState;
use App\Models\Scanner;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExportOfficeAttendanceAction extends BulkAction
{
    public bool $transmittal = false;

    public static function make(?string $name = null, bool $transmittal = false): static
    {
        $class = static::class;

        $name = $transmittal ? 'export-attendance-transmittal' : 'export-attendance';

        $static = app($class, ['name' => $name]);

        $static->transmittal = $transmittal;

        $static->configure();

        return $static;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->icon(fn () => $this->transmittal ? 'heroicon-o-clipboard-document-check' : 'heroicon-o-clipboard-document-list');

        $this->modalHeading(fn () => $this->transmittal ? 'Export Transmittal' : 'Export Attendance');

        $this->label(fn () => $this->transmittal ? 'Export Transmittal' : 'Export Attendance');

        $this->modalDescription('');

        $this->modalIcon('heroicon-o-document-arrow-down');

        $this->modalWidth('2xl');

        $this->form($this->exportForm());

        $this->action(fn (Collection $records, array $data) => $this->exportAction($records, $data));
    }

    public function exportAction(Collection|Model $office, array $data): StreamedResponse|BinaryFileResponse|Notification
    {
        $actionException = new class extends Exception
        {
            public function __construct(public readonly ?string $title = null, public readonly ?string $body = null)
            {
                parent::__construct();
            }
        };

        try {
            if ($office instanceof Collection && $office->count() > 100) {
                throw new $actionException('Too many records', 'To prevent server overload, please select less than 100 records');
            }

            return (new ExportAttendance)
                ->office($office)
                ->dates($data['dates'])
                ->from($data['from'])
                ->to($data['to'])
                ->scanners($data['scanners'])
                ->states($data['states'])
                ->modes($data['modes'])
                ->status($data['status'])
                ->substatus($data['substatus'])
                ->strict($data['strict'])
                ->size($data['size'])
                ->signature($data['electronic_signature'] ? auth()->user()->signature : null)
                ->password($data['digital_signature'] ? $data['password'] : null)
                ->transmittal($this->transmittal)
                ->download();
        } catch (ProcessFailedException $exception) {
            $message = $office instanceof Collection ? 'Failed to export timesheets' : "Failed to export {$office->name}'s timesheet";

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

    public function exportForm(): array
    {
        return [
            Group::make()
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->multiple()
                        ->options(collect(EmploymentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])),
                    Select::make('substatus')
                        ->multiple()
                        ->options(collect(EmploymentSubstatus::cases())->mapWithKeys(fn ($substatus) => [$substatus->value => $substatus->getLabel()])),
                    Select::make('states')
                        ->multiple()
                        ->options(collect(TimelogState::cases())->mapWithKeys(fn ($state) => [$state->value => $state->getLabel()])),
                    Select::make('modes')
                        ->multiple()
                        ->options(collect(TimelogMode::cases())->mapWithKeys(fn ($mode) => [$mode->value => $mode->getLabel(1)])),
                ]),
            Select::make('scanners')
                ->multiple()
                ->options(Scanner::whereNotNull('uid')->orderBy('name')->pluck('name', 'uid')->toArray())
                ->dehydrateStateUsing(fn ($state) => Scanner::whereIn('uid', $state)->orderBy('name')->get())
                ->preload(),
            Select::make('size')
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
            Group::make()
                ->columns(2)
                ->schema([
                    TextInput::make('from')
                        ->type('time')
                        ->rule('date_format:H:i'),
                    TextInput::make('to')
                        ->type('time')
                        ->rule('date_format:H:i'),
                ]),
            Repeater::make('dates')
                ->addActionLabel('Add new')
                ->reorderable(false)
                ->grid(3)
                ->required()
                ->cloneable()
                ->simple(
                    TextInput::make('date')
                        ->type('date')
                        ->markAsRequired()
                        ->rule('required')
                ),
            Checkbox::make('strict')
                ->label('Strict listing')
                ->hintIcon('heroicon-o-no-symbol')
                ->hintIconTooltip('When checked, filters out all employees who do not have a record on the selected date.')
                ->default(true),
            Checkbox::make('electronic_signature')
                ->hintIcon('heroicon-o-check-badge')
                ->hintIconTooltip('Electronically sign the document. This does not provide security against tampering.')
                ->default(fn ($livewire) => $livewire->filters['electronic_signature'] ?? false)
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
