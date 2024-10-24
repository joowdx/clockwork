<?php

namespace App\Filament\Actions\TableActions;

use App\Models\Timesheet;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;

class DownloadTimesheetAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('download-timesheet');

        $this->requiresConfirmation();

        $this->color('gray');

        $this->hidden(fn (Timesheet $record) => $record->exports->isEmpty());

        $this->requiresConfirmation();

        $this->icon('heroicon-o-document-arrow-down');

        $this->modalIcon('heroicon-o-document-arrow-down');

        $this->modalSubmitActionLabel('Download');

        $this->modalDescription(function (Timesheet $record) {
            $period = match ($record->span) {
                'full' => 'month',
                default => "{$record->span} half",
            };

            $month = Carbon::parse($record->month)->format('F Y');

            $description = <<<HTML
                Are you sure you want to download the timesheet of <br>
                {$record->employee->titled_name} for the {$period} of {$month}?
            HTML;

            return str($description)->toHtmlString();
        });

        $this->action(function (Timesheet $record) {
            $from = str_pad($record->from, 2, '0', STR_PAD_LEFT);

            $name = "{$record->month} {$from}-{$record->to} ".trim($record->employee->name, '.').'.pdf';

            $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

            return response()->streamDownload(fn () => print ($record->export->content), $name, $headers);
        });
    }
}
