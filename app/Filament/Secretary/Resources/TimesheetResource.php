<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Actions\TableActions\BulkAction\DeleteTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTransmittalAction;
use App\Filament\Actions\TableActions\BulkAction\GenerateTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ViewTimesheetAction;
use App\Filament\Actions\TableActions\UpdateEmployeeAction;
use App\Filament\Filters\ActiveFilter;
use App\Filament\Filters\OfficeFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Secretary\Resources\TimesheetResource\Pages;
use App\Models\Employee;
use App\Models\Office;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimesheetResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'gmdi-document-scanner-o';

    protected static ?string $navigationLabel = 'Timesheets';

    protected static ?string $breadcrumb = 'Timesheets';

    protected static ?string $modelLabel = 'Timesheets';

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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->searchable()
                    ->formatStateUsing(function (Employee $record) {
                        $offices = $record->offices->map(function ($office) {
                            return str($office->code)
                                ->when($office->pivot->current, function ($code) {
                                    return <<<HTML
                                        <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--primary-400);--c-600:var(--primary-600);">$code</span>
                                    HTML;
                                });
                        })->join(', ');

                        return str($offices)->toHtmlString();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->limit(24)
                    ->getStateUsing(function (Employee $employee): string {
                        return str($employee->status?->value)
                            ->title()
                            ->when($employee->substatus?->value, function ($status) use ($employee) {
                                return $status->append(" ({$employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
            ])
            ->filters([
                ActiveFilter::make(),
                StatusFilter::make(),
                // SelectFilter::make('offices')
                //     ->multiple()
                //     ->preload()
                // ->relationship('offices', 'name', function ($query) {
                //     $query->whereIn('offices.id', auth()->user()->offices->pluck('id'));

                //     $query->orWhereHas('employees', function ($query) {
                //         $query->whereHas('scanners', function (Builder $query) {
                //             $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                //         });
                //     });
                // }),
                OfficeFilter::make(),
                SelectFilter::make('groups')
                    ->relationship(
                        'groups',
                        'name',
                        fn ($query) => $query->whereHas('employees', function ($query) {
                            $user = user();

                            $query->whereHas('offices', function ($query) use ($user) {
                                $query->whereIn('offices.id', $user->offices->pluck('id'));
                            })
                                ->orWhereHas('scanners', function (Builder $query) use ($user) {
                                    $query->whereIn('scanners.id', $user->scanners->pluck('id')->toArray());
                                });
                        })
                    )
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                UpdateEmployeeAction::make(),
            ])
            ->bulkActions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View'),
                Tables\Actions\BulkActionGroup::make([
                    ExportTimesheetAction::make()
                        ->label('Timesheet'),
                    ExportTransmittalAction::make()
                        ->label('Transmittal'),
                ])
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down'),
                GenerateTimesheetAction::make()
                    ->label('Generate'),
                DeleteTimesheetAction::make('delete'),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('name', 'asc')
            ->deselectAllRecordsWhenFiltered(false);
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $user = user();

                $query->whereHas('offices', function (Builder $query) use ($user) {
                    $query->whereIn('offices.id', $user->offices->pluck('id')->toArray());
                });

                $query->orWhereHas('scanners', function (Builder $query) use ($user) {
                    $query->whereIn('scanners.id', $user->scanners->pluck('id')->toArray());
                });
            });
    }
}
