<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Actions\TableActions\BulkAction\ExportTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\GenerateTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ViewTimesheetAction;
use App\Filament\Filters\ActiveFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Secretary\Resources\TimesheetResource\Pages;
use App\Filament\Secretary\Resources\TimesheetResource\RelationManagers;
use App\Models\Employee;
use App\Models\Timesheet;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\SelectAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TimesheetResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'gmdi-document-scanner-o';

    protected static ?string $navigationLabel = 'Timesheets';

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
                    ->searchable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->searchable(),
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
                SelectFilter::make('offices')
                    ->relationship('offices', 'name', fn ($query) => $query->whereIn('offices.id', auth()->user()->offices->pluck('id')))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('groups')
                    ->relationship(
                        'groups',
                        'name',
                        fn ($query) => $query->whereHas('employees', function ($query) {
                            $query->whereHas('offices', function ($query) {
                                $query->whereIn('offices.id', auth()->user()->offices->pluck('id'));
                            })
                            ->orWhereHas('scanners', function (Builder $query) {
                                $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                            });
                        })
                    )
                    ->multiple()
                    ->preload(),
            ])
            ->bulkActions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View'),
                Tables\Actions\BulkActionGroup::make([
                    ExportTimesheetAction::make()
                        ->label('Timesheet'),
                    Tables\Actions\BulkAction::make('transmittal')
                        ->icon('heroicon-o-clipboard-document-check')
                ])
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down'),
                GenerateTimesheetAction::make()
                    ->label('Generate'),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereHas('offices', function (Builder $query) {
                    $query->whereIn('offices.id', auth()->user()->offices->pluck('id')->toArray());
                })
                ->orWhereHas('scanners', function (Builder $query) {
                    $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                });
            });
    }
}
