<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Actions\TableActions\BulkActionGroup\DownloadTimesheetAction;
use App\Filament\Actions\TableActions\BulkActionGroup\VerifyTimesheetAction;
use App\Filament\Actions\TableActions\ViewTimesheetAction;
use App\Filament\Filters\OfficeFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Manager\Resources\TimesheetResource\Pages;
use App\Models\Employee;
use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;

    protected static ?string $navigationIcon = 'gmdi-document-scanner-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->certified();

                $query->with([
                    'employee',
                    'fullMonth' => fn ($query) => $query->select(['id', 'punch', 'present', 'half', 'timesheet_id'])->whereNot('punch', '[]'),
                    'exports' => fn ($query) => $query->select(['id', 'exportable_id', 'exportable_type', 'details']),
                ]);

                $query->whereHas('employee');

                $query->when(Filament::getCurrentPanel()->getId() === 'director', function ($query) {
                    $query->whereHas('employee.offices', function (Builder $query) {
                        $query->where('offices.id', Auth::user()->employee?->currentDeployment?->office?->id);

                        $query->where('deployment.current', true);
                    });
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.status')
                    ->label('Status')
                    ->limit(24)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (Timesheet $record): string {
                        return str($record->employee->status?->value)
                            ->title()
                            ->when($record->employee->substatus?->value, function ($status) use ($record) {
                                return $status->append(" ({$record->employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
                Tables\Columns\TextColumn::make('employee.offices.code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function (Timesheet $record) {
                        $offices = $record->employee->offices->map(function ($office) {
                            return str($office->code)
                                ->when($office->pivot->current, function ($code) {
                                    return <<<HTML
                                        <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--primary-400);--c-600:var(--primary-600);">$code</span>
                                    HTML;
                                });
                        })->join(', ');

                        return str($offices)->toHtmlString();
                    }),
                Tables\Columns\TextColumn::make('month')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employee.uid')
                    ->label('UID')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('days'),
                Tables\Columns\TextColumn::make('timetables_count')
                    ->toggleable()
                    ->label('Absences')
                    ->counts(['timetables' => fn ($query) => $query->where('absent', true)]),
                Tables\Columns\TextColumn::make('timetables_sum_undertime')
                    ->toggleable()
                    ->label('Undertime')
                    ->sum('timetables', 'undertime'),
                Tables\Columns\TextColumn::make('timetables_sum_overtime')
                    ->toggleable()
                    ->label('Overtime')
                    ->sum('timetables', 'overtime'),
                Tables\Columns\TextColumn::make('missed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('certified')
                    ->toggleable()
                    ->state(function (Timesheet $record) {
                        $certified = collect($record->certified)
                            ->filter()
                            ->keys()
                            ->map(fn ($key) => $key === 'full' ? 'Full month' : ucfirst("$key half"));

                        return $certified->join(', ');
                    })
                    ->placeholder(str('<i>(none)</i>')->toHtmlString()),
                Tables\Columns\TextColumn::make('verified')
                    ->toggleable()
                    ->state(function (Timesheet $record) {
                        $verified = collect($record->verified)
                            ->filter()
                            ->keys()
                            ->map(fn ($key) => $key === 'full' ? 'Full month' : ucfirst("$key half"));

                        return $verified->join(', ');
                    })
                    ->placeholder(str('<i>(none)</i>')->toHtmlString()),
            ])
            ->filters([
                Tables\Filters\Filter::make('month')
                    ->form([
                        Forms\Components\TextInput::make('month')
                            ->type('month'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(isset($data['month']), fn ($query) => $query->where('month', Carbon::parse($data['month'])->format('Y-m-01')));
                    })
                    ->indicateUsing(function (array $data) {
                        if (empty($data['month'])) {
                            return null;
                        }

                        return 'Month: '.Carbon::parse($data['month'])->format('F Y');
                    }),
                StatusFilter::make()
                    ->relationship('employee'),
                OfficeFilter::make()
                    ->relationship('employee'),
            ])
            ->actions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View')
                    ->slideOver(),
                // CertifyTimesheetAction::make(),
                // DownloadTimesheetAction::make()
                //     ->label('Download'),
            ])
            ->bulkActions([
                VerifyTimesheetAction::make(),
                DownloadTimesheetAction::make(),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderBy('month', 'desc')->orderBy(Employee::select('name')->whereColumn('employee_id', 'employees.id')))
            ->recordAction(null)
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
        ];
    }
}
