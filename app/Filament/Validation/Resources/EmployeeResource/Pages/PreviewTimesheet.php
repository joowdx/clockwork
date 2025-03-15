<?php

namespace App\Filament\Validation\Resources\EmployeeResource\Pages;

use App\Filament\Validation\Resources\EmployeeResource;
use App\Models\Timesheet;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class PreviewTimesheet extends ViewRecord
{
    #[Url]
    public string $period = '';

    #[Url]
    public string $month = '';

    protected static string $resource = EmployeeResource::class;

    public function mount(int|string $record): void
    {
        $employee = static::$resource::getModel()::where('uid', $record)->firstOrFail();

        abort_unless(
            in_array($this->period, ['1st', '2nd', 'full']) &&
            Carbon::parse($this->month)->format('Y-m') === $this->month,
            404
        );

        $this->record = $employee->timesheets()
            ->where('month', "{$this->month}-01")
            ->firstOrFail();

        $this->record->setSpan($this->period);

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }

    public function getTitle(): string
    {
        return $this->record->employee->name;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Infolists\Components\TextEntry::make('timesheet')
                            ->columnSpan(5)
                            ->formatStateUsing(function (): View {
                                return view('filament.validation.pages.csc', [
                                    'timesheets' => [$this->record->setSpan($this->period)],
                                    'styles' => false,
                                    'month' => false,
                                ]);
                            }),
                        Infolists\Components\TextEntry::make('timelogs')
                            ->columnSpan(7)
                            ->formatStateUsing(function (): View {
                                $month = Carbon::parse($this->record->month);

                                $from = $this->period === '2nd' ? 16 : 1;

                                $to = $this->period === '1st' ? 15 : $month->daysInMonth();

                                return view('filament.validation.pages.default', [
                                    'employees' => [$this->record->employee->load(['scanners', 'timelogs'])],
                                    'month' => $month,
                                    'period' => $this->filters['period'] ?? 'full',
                                    'from' => $from,
                                    'to' => $to,
                                    'preview' => true,
                                ]);
                            }),
                    ]),
                Infolists\Components\TextEntry::make('scanners')
                    ->columnSpanFull()
                    ->state(function () {
                        $scanners = $this->record->employee->scanners()
                            ->orderBy('priority', 'desc')
                            ->orderBy('name')
                            ->get()
                            ->map(function ($scanner) {
                                return <<<HTML
                                    <span
                                        class="px-2 py-1 mr-2 font-xs text-nowrap"
                                        style="border-radius:0.3em;background-color:{$scanner->background_color};text-color:{$scanner->foreground_color};"
                                    >
                                        {$scanner->name} ({$scanner->pivot->uid})
                                    </span>
                                HTML;
                            });

                        return str($scanners->join(''))
                            ->wrap('<span>', '</span>')
                            ->toHtmlString();
                    }),
                Infolists\Components\Group::make([
                    Infolists\Components\TextEntry::make('days')
                        ->label('Days'),
                    Infolists\Components\TextEntry::make('overtime')
                        ->label('Overtime')
                        ->state(function (Timesheet $record) {
                            return $record->getOvertime(true);
                        }),
                    Infolists\Components\TextEntry::make('undertime')
                        ->label('Undertime')
                        ->state(function (Timesheet $record) {
                            return $record->getUndertime(true);
                        }),
                    Infolists\Components\TextEntry::make('missed')
                        ->label('Missed')
                        ->state(function (Timesheet $record) {
                            return $record->getMissed(true);
                        }),
                ]),
            ]);
    }
}
