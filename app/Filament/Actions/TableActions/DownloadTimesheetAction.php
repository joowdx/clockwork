<?php

namespace App\Filament\Actions\TableActions;

use App\Models\Export;
use App\Models\Timesheet;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use ZipArchive;

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

        $this->modalDescription('');

        $this->form([
            Select::make('period')
                ->required()
                ->multiple()
                ->default(fn (Timesheet $record) => $record->exports->map->details->map->period->toArray())
                ->options(function (Timesheet $record) {
                    return $record->exports->mapWithKeys(function (Export $export) {
                        return [
                            $export->details->period => match ($export->details->period) {
                                '1st' => 'First half',
                                '2nd' => 'Second half',
                                default => 'Full month',
                            },
                        ];
                    });
                }),
        ]);

        $this->action(function (Timesheet $record, array $data) {
            $exports = $record->exports()->whereIn('details->period', $data['period'])->get();

            $name = "$record->month-".trim($record->employee->name, '.').'.zip';

            $headers = ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

            $temp = stream_get_meta_data(tmpfile())['uri'];

            $zip = new ZipArchive;

            $zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $zip->setCompressionIndex(-1, ZipArchive::CM_STORE);

            $exports->each(function (Export $export) use ($zip) {
                $zip->addFromString($export->filename, $export->content);
            });

            $zip->close();

            return response()->download($temp, $name, $headers)->deleteFileAfterSend();
        });
    }
}
