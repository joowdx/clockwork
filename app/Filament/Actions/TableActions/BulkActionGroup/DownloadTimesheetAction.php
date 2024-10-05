<?php

namespace App\Filament\Actions\TableActions\BulkActionGroup;

use App\Forms\Components\TimesheetOption;
use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Collection;
use ZipArchive;

class DownloadTimesheetAction extends BulkActionGroup
{
    protected string|false|null $level = null;

    protected array $periods = [
        '1st',
        '2nd',
        'full',
    ];

    public static function make(array $actions = []): static
    {
        $static = app(static::class, ['actions' => $actions]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->level = match (Filament::getCurrentPanel()->getId()) {
            'director' => 'head',
            'supervisor' => 'supervisor',
            'employee' => null,
            default => false,
        };

        $this->label('Download');

        $this->icon('heroicon-o-document-arrow-down');

        $this->actions(array_map(fn ($period) => $this->downloadAction($period), $this->periods));
    }

    protected function downloadAction(string $period): BulkAction
    {
        $label = match ($period) {
            'full' => 'Full Month',
            default => "{$period} Half",
        };

        return BulkAction::make("download-{$period}")
            ->label($label)
            ->requiresConfirmation()
            ->modalIcon('gmdi-fact-check-o')
            ->modalHeading('Download certified timesheets')
            ->modalDescription('Download selected timesheet\'s '.strtolower($label).(in_array($period, ['1st', '2nd']) ? ' of the month' : ''))
            ->slideOver()
            ->successNotificationTitle('Timesheets will be downloaded shortly.')
            ->failureNotificationTitle('Download failed.')
            ->modalWidth('max-w-lg')
            ->form(function (Collection $records) use ($period) {
                $records = $records->toQuery()
                    ->whereHas('exports', fn ($q) => $q->where('details->period', $period)->whereNotNull("details->verification->{$this->level}->at"))
                    ->get()
                    ->each
                    ->setSpan($period);

                return [
                    TimesheetOption::make('timesheets')
                        ->bulkToggleable()
                        ->records($records)
                        ->options($records->mapWithKeys(fn ($record) => [$record->id => $record->employee->name])->toArray())
                        ->label('Timesheets')
                        ->required(),
                ];
            })
            ->action(function (BulkAction $action, Collection $records, array $data) use ($period) {
                $records = $records->toQuery()
                    ->whereIn('id', $data['timesheets'])
                    ->whereHas('exports', fn ($q) => $q->where('details->period', $period))
                    ->with(['exports' => fn ($q) => $q->where('details->period', $period)])
                    ->get();

                if ($records->isEmpty()) {
                    $action->sendFailureNotification();

                    return;
                }

                $temp = stream_get_meta_data(tmpfile())['uri'];

                $zip = new ZipArchive;

                $zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                $zip->setCompressionIndex(-1, ZipArchive::CM_STORE);

                $records->each(fn (Timesheet $timesheet) => $zip->addFromString($timesheet->exports->first()->filename, $timesheet->exports->first()->content));

                $zip->close();

                $action->sendSuccessNotification();

                $name = 'Timesheets.zip';

                $headers = ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

                return response()->download($temp, $name, $headers)->deleteFileAfterSend();
            });
    }
}
