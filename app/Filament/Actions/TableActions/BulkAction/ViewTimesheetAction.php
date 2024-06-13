<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Models\Timesheet;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ViewTimesheetAction extends BulkAction
{
    protected bool $listing = false;

    public static function make(?string $name = null, bool $listing = false): static
    {
        $class = static::class;

        $name ??= ($listing ? 'bulk-view-timesheet' : 'bulk-view-timesheet-form');

        $static = app($class, ['name' => $name]);

        $static->listing = $listing;

        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->listing) {
            $this->extraAttributes(['class' => 'hidden']);

            $this->icon('gmdi-document-scanner-o');

            $this->modalHeading('View timesheets');

            $this->modalWidth(fn ($livewire) => $livewire->mountedTableBulkActionData['format'] === 'csc' ? 'xl' : '3xl');

            $this->modalSubmitAction(false);

            $this->modalContent(function ($records, $livewire) {
                $data = $livewire->mountedTableBulkActionData;

                if ($data['format'] === 'csc') {
                    return View::make('print.csc')->view('print.csc', [
                        'preview' => true,
                        'timesheets' => Timesheet::whereHas('employee', fn ($query) => $query->whereIn('id', $records->pluck('id')))
                            ->whereDate('month', $data['month'].'-01')->get(),
                    ]);
                }

                $month = $month = Carbon::parse($data['month']);

                $from = $month->clone();

                $to = $month->clone()->endOfMonth();

                $timelogs = fn ($query) => $query->whereBetween('time', [$from, $to]);

                return View::make('print.default')->view('print.default', [
                    'month' => $month,
                    'from' => $from->day,
                    'to' => $to->day,
                    'employees' => $records->load(['timelogs' => $timelogs, 'timelogs.scanner', 'scanners']),
                    'preview' => true,
                ]);
            });

            $this->modalCancelActionLabel('Close');

            $this->modalFooterActionsAlignment('end');

            $this->slideOver();
        } else {
            $this->name = 'view-timesheet-form';

            $this->requiresConfirmation();

            $this->color('gray');

            $this->modalHeading('View timesheets');

            $this->modalDescription(fn (Collection $records) => "View timesheets of {$records->count()} selected ".str('employee')->plural($records->count()).'.');

            $this->modalIcon('gmdi-document-scanner-o');

            $this->label('View Timesheets');

            $this->icon('gmdi-document-scanner-o');

            $this->form([
                TextInput::make('month')
                    ->markAsRequired()
                    ->rule('required')
                    ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                    ->type('month'),
                Select::make('format')
                    ->live()
                    ->placeholder('Print format')
                    ->default('csc')
                    ->required()
                    ->options(['default' => 'Default format', 'csc' => 'CSC format']),
            ]);

            $this->action(fn ($records, $livewire) => $livewire->replaceMountedTableBulkAction('bulk-view-timesheet', $records->toArray()));
        }
    }
}
