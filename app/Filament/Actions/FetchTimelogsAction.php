<?php

namespace App\Filament\Actions;

use App\Jobs\FetchTimelogs;
use App\Models\Scanner;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class FetchTimelogsAction extends Action
{
    protected bool $onlyAssigned = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name ??= 'fetch-timelogs';

        $this->requiresConfirmation();

        $this->icon('heroicon-m-arrow-down-tray');

        $this->groupedIcon('heroicon-m-arrow-down-tray');

        $this->modalIcon('heroicon-o-arrow-down-tray');

        $this->modalDescription('Directly fetch timelogs from the scanner to the system.');

        $this->closeModalByClickingAway(false);

        $this->slideOver();

        $this->form([
            TextInput::make('month')
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->rules(['required'])
                ->live()
                ->markAsRequired(true)
                ->type('month')
                ->helperText(function () {
                    return 'Timelogs outside the specified month will be ignored.';
                }),
            CheckboxList::make('scanners')
                ->options(fn () => $this->onlyAssigned
                        ? auth()->user()->scanners()->reorder()->orderByDesc('priority')->orderBy('name')->pluck('name', 'scanners.id')->toArray()
                        : Scanner::query()->reorder()->orderByDesc('priority')->orderBy('name')->pluck('name', 'id')->toArray()
                )
                ->rules(['required'])
                ->live()
                ->markAsRequired(true)
                ->columns(2)
                ->helperText(function () {
                    return 'Select the scanners to fetch timelogs from.';
                }),
        ]);

        $this->action(function (array $data) {
            $scanners = Scanner::findMany($data['scanners']);

            $filtered = $scanners->filter->host;

            $rejected = $scanners->reject->host;

            if ($rejected->isNotEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('Skipping unconfigured scanners')
                    ->body("Scanners [{$rejected->pluck('name')->join(', ')}] are not yet configured. Please set them up first before using this feature.")
                    ->send();
            }

            if ($filtered->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No valid scanners to fetch timelogs from')
                    ->send();

                return;
            }

            $filtered->each(function (Scanner $scanner) use ($data) {
                FetchTimelogs::dispatch($scanner->uid, $data['month'])
                    ->onQueue('main');
            });

            Notification::make()
                ->success()
                ->title('Timelogs fetching initiated')
                ->body("The timelogs will be fetched for scanners [{$filtered->pluck('name')->join(', ')}] in the background. Please wait for the process to complete.")
                ->send();
        });

        $this->modalSubmitActionLabel('Fetch');

        $this->modalCancelActionLabel('Close');
    }

    public function onlyAssigned(bool $true = true)
    {
        $this->onlyAssigned = $true;

        return $this;
    }
}
