<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Filters\OfficeFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Manager\Resources\TimesheetResource\Pages;
use App\Filament\Manager\Resources\TimesheetResource\RelationManagers;
use App\Models\Employee;
use App\Models\Timesheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

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
            ->modifyQueryUsing(fn ($query) => $query->whereHas('employee'))
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.status')
                    ->limit(24)
                    ->getStateUsing(function (Timesheet $record): string {
                        return str($record->employee->status?->value)
                            ->title()
                            ->when($record->employee->substatus?->value, function ($status) use ($record) {
                                return $status->append(" ({$record->employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
                Tables\Columns\TextColumn::make('employee.offices.code')
                    ->searchable()
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
                Tables\Columns\TextColumn::make('month'),
                Tables\Columns\TextColumn::make('employee.uid')
                    ->label('UID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('month')
                    ->form([
                        Forms\Components\TextInput::make('month')
                            ->type('month')
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(isset($data['month']), fn ($query) => $query->where('month', Carbon::parse($data['month'])->format('Y-m-01')));
                    })
                    ->indicateUsing(function (array $data) {
                        if (empty($data['month'])) {
                            return null;
                        }

                        return 'Month: '. Carbon::parse($data['month'])->format('F Y');
                    }),
                StatusFilter::make()
                    ->relationship('employee'),
                OfficeFilter::make()
                    ->relationship('employee'),
            ])
            ->actions([

            ])
            ->bulkActions([

            ])
            ->defaultSort(fn (Builder $query) => $query->orderBy('month', 'desc')->orderBy(Employee::select('name')->whereColumn('employee_id', 'employees.id')))
            ->recordAction(null)
            ->recordUrl(null)
            ;
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
            'create' => Pages\CreateTimesheet::route('/create'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }
}
