<?php

namespace App\Filament\Actions;

use App\Jobs\ImportTimelogs;
use App\Models\Scanner;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use League\Csv\Reader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportTimelogsAction extends Action
{
    protected bool $onlyAssigned = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name ??= 'import-timelogs';

        $this->requiresConfirmation();

        $this->icon('heroicon-m-arrow-up-tray');

        $this->groupedIcon('heroicon-m-arrow-up-tray');

        $this->modalIcon('heroicon-o-arrow-up-tray');

        $this->modalDescription('');

        $this->closeModalByClickingAway(false);

        $this->action(function (array $data) {
            if (is_null($data['files'])) {
                return;
            }

            $devices = [];

            foreach ($data['files'] as $file) {
                $first = Reader::createFromStream(fopen($file->getRealPath(), mode: 'r'))
                    ->addFormatter(fn ($row) => array_map('trim', $row))
                    ->setDelimiter("\t")
                    ->first();

                $devices[] = $first[2];

                ImportTimelogs::dispatchSync($first[2], $file->getRealPath(), $file->getClientOriginalName(), $data['month']);
            }

            $devices = implode(', ', $devices);

            Notification::make()
                ->success()
                ->title('Upload successful')
                ->body(str("We'll notify you once the timelogs for devices <b>{$devices}</b> have been imported.")->toHtmlString())
                ->send();
        });

        $this->form([
            Radio::make('process')
                ->hidden()
                ->label('Process')
                ->live()
                ->dehydrated(false)
                ->default(true)
                ->options([
                    false => 'Ignore',
                    true => 'Enable',
                ])
                ->descriptions([
                    false => 'Disable processing.',
                    true => 'Only process timelogs of the specified month.',
                ])
                ->afterStateUpdated(function (Get $get, Set $set, bool $state, $livewire) {
                    if ($state) {
                        $set('month', $get('month') ?? today()->format('Y-m'));

                        $livewire->validateOnly('month');
                    }
                }),
            TextInput::make('month')
                ->visible(fn (Get $get) => $get('process'))
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->rules(['required'])
                ->live()
                ->markAsRequired(true)
                ->type('month')
                ->helperText(function () {
                    return 'Timelogs outside the specified month will be ignored.';
                }),
            FileUpload::make('files')
                ->helperText('Please ensure that the device UID in the file matches the device UID in the system to avoid errors.')
                // ->afterStateUpdated(fn ($livewire) => $livewire->resetValidation())
                ->multiple()
                ->storeFiles(false)
                ->visibility('private')
                ->maxSize(8096)
                ->required()
                ->rules([
                    'mimes:csv,txt',
                    fn () => function (string $attribute, TemporaryUploadedFile $value, \Closure $fail) {
                        $first = Reader::createFromStream(fopen($value->getRealPath(), mode: 'r'))
                            ->addFormatter(fn ($row) => array_map('trim', $row))
                            ->setDelimiter("\t")
                            ->first();

                        $file = $value->getCLientOriginalName();

                        $number = substr_replace($file, '', strrpos($file, '_attlog.dat'), strlen('_attlog.dat'));

                        if (is_numeric($number) && $number <= 255 && $number !== $first[2]) {
                            $fail("File uploaded {$file} is invalid");
                        }

                        if (count($first) !== 6) {
                            $fail("File uploaded {$file} is invalid");
                        }

                        if (isset($first[2]) && is_numeric($first[2])) {
                            if (
                                Scanner::where('uid', $first[2])
                                    ->when($this->onlyAssigned, fn ($query) => $query->whereIn('scanners.uid', auth()->user()->scanners->pluck('uid')))
                                    ->doesntExist()
                            ) {
                                $fail("No device found for these records from device {$first[2]} with UID in file $file");
                            }
                        } else {
                            $fail("File uploaded {$file} is invalid");
                        }
                    },
                ]),
        ]);
    }

    public function onlyAssigned(bool $true = true)
    {
        $this->onlyAssigned = $true;

        return $this;
    }
}
