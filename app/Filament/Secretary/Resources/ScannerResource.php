<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Actions\TableActions\FetchAction;
use App\Filament\Secretary\Resources\ScannerResource\Pages;
use App\Filament\Superuser\Resources\ScannerResource as SuperuserScannerResource;
use App\Filament\Superuser\Resources\ScannerResource\RelationManagers\EmployeesRelationManager;
use App\Models\Scanner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ScannerResource extends Resource
{
    protected static ?string $model = Scanner::class;

    protected static ?string $navigationIcon = 'gmdi-touch-app-o';

    public static function form(Form $form): Form
    {
        return $form->schema(SuperuserScannerResource::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->extraCellAttributes(['class' => 'font-mono'])
                    ->placeholder('<blank>')
                    ->label('UID')
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw("CAST(uid as INT) $direction"))
                    ->searchable(query: fn ($query, $search) => $query->whereRaw("CAST(uid as TEXT) = '$search'")),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts(['employees' => fn ($query) => $query->where('enrollment.active', true)])
                    ->sortable(),
                Tables\Columns\TextColumn::make('timelogs_count')
                    ->label('Timelogs')
                    ->counts('timelogs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                FetchAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->currentPassword()
                                ->markAsRequired()
                                ->rules(['required', 'string']),
                        ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScanners::route('/'),
            'edit' => Pages\EditScanner::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('scanners.id', Auth::user()->scanners?->pluck('id')->toArray());
    }
}
