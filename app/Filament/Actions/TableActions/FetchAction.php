<?php

namespace App\Filament\Actions\TableActions;

use App\Jobs\FetchTimelogs;
use App\Models\Scanner;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Http;

class FetchAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('fetch-timelogs');

        $this->visible(! config('app.remote.server'));

        $this->hidden(config('app.remote.server'));

        $this->label('Fetch');

        $this->icon('heroicon-m-arrow-path-rounded-square');

        $this->requiresConfirmation();

        $this->modalIcon('heroicon-m-arrow-path-rounded-square');

        $this->modalHeading('Fetch timelogs');

        $this->closeModalByClickingAway(false);

        $this->modalDescription(function (Scanner $record) {
            if (empty($record->host)) {
                return 'Device connection is not yet configured. Please set it up first before using this feature.';
            }

            return 'Would you like to fetch timelogs directly from the scanner?';
        });

        $this->modalCancelActionLabel(function (Scanner $record) {
            if (empty($record->host)) {
                return 'Close';
            }
        });

        $this->modalSubmitAction(function (Scanner $record) {
            if (empty($record->host)) {
                return false;
            }
        });

        $this->form([
            Toggle::make('filter')
                ->hidden()
                ->label('Process all')
                ->live()
                ->dehydrated(false)
                ->default(false)
                ->afterStateUpdated(function (Get $get, Set $set, bool $state, $livewire) {
                    if ($state) {
                        $set('month', $get('month') ?? today()->format('Y-m'));

                        $livewire->validateOnly('month');
                    }
                }),
            TextInput::make('month')
                ->disabled(fn (Get $get) => $get('filter'))
                ->hidden(fn (Scanner $record) => empty($record->host))
                ->helperText(function () {
                    return 'Only process the timelogs of the specified month. However, this will still attempt to fetch all timelogs directly from the terminal due to the limitation of the device.';
                })
                ->default(today()->format('Y-m'))
                ->live()
                ->markAsRequired(true)
                ->type('month')
                ->rules(['required']),
        ]);

        $this->action(function (Scanner $record, array $data) {
            if (empty($record->uid) || empty($record->host)) {
                Notification::make()
                    ->danger()
                    ->title('Fetch failed')
                    ->body("You need to set the device's uid and its hostname or ip address first.")
                    ->send();

                return;
            }

            if (config('app.remote.server')) {
                Http::withToken(config('app.remote.token'))
                    ->post(config('app.host') . '/api/fetch', [
                        'callback' => config('app.url') . '/api/fetch/receive',
                        'host' => $record->host,
                        'port' => $record->port,
                        'pass' => $record->pass,
                        'month' => $data['month'],
                        'user' => user()->id,
                        'token' => app(Encrypter::class, ['key' => config('app.passkey')])->encrypt(config('app.token')),
                    ]);
            } else {
                FetchTimelogs::dispatch($record->uid, $data['month'])
                    ->onQueue('main');
            }

            Notification::make()
                ->success()
                ->title('Command queued')
                ->body(str("We'll notify you once the timelogs of {$record->name} have been fetched.")->toHtmlString())
                ->send();
        });
    }
}
