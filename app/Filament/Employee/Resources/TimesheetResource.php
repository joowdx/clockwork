<?php

namespace App\Filament\Employee\Resources;

use App\Filament\Actions\TableActions\ViewTimesheetAction;
use App\Filament\Employee\Resources\TimesheetResource\Pages;
use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
            ->modifyQueryUsing(fn ($query) => $query->with('timetables')->where('employee_id', Filament::auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->extraCellAttributes(['class' => 'font-mono'])
                    ->state(fn (Timesheet $record) => Carbon::parse($record->month)->format('M Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('days'),
                Tables\Columns\TextColumn::make('timetables_count')
                    ->label('Absences')
                    ->counts(['timetables' => fn ($query) => $query->where('absent', true)]),
                Tables\Columns\TextColumn::make('timetables_sum_undertime')
                    ->label('Undertime')
                    ->sum('timetables', 'undertime'),
                Tables\Columns\TextColumn::make('timetables_sum_overtime')
                    ->label('Overtime')
                    ->sum('timetables', 'overtime'),
                Tables\Columns\TextColumn::make('misses'),
            ])
            ->filters([

            ])
            ->actions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View'),
                // Tables\Actions\Action::make('update')
                //     ->requiresConfirmation()
                //     ->modalIcon('gmdi-edit-o')
                //     ->modalDescription(fn (Timesheet $record) => "Update " . Carbon::parse($record->month)->format('F Y') .  " timesheet information")
                //     ->form([
                //         Toggle::make('supervisor_field')
                //             ->label('Supervisor')
                //             ->reactive(),
                //         TextInput::make('supervisor')
                //             ->helperText('Direct supervisor\'s name')
                //             ->visible(fn (Get $get) => $get('supervisor_field')),
                //         TextInput::make('head')
                //             ->label('Department head')
                //             ->helperText('Department head\'s name'),
                //     ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('month', 'desc');
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
