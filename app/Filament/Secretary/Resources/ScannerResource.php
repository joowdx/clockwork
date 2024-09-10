<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Actions\TableActions\FetchAction;
use App\Filament\Secretary\Resources\ScannerResource\Pages;
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
        return $form
            ->schema([
                Forms\Components\Section::make('Scanner Details')
                    ->columns()
                    ->schema([
                        Forms\Components\ToggleButtons::make('priority')
                            ->required()
                            ->boolean()
                            ->grouped()
                            ->inline()
                            ->default(false)
                            ->columnSpanFull()
                            ->helperText('Prioritized scanners have higher precedence over others.'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->alphaDash()
                            ->dehydrateStateUsing(fn (string $state): ?string => mb_strtolower($state)),
                        Forms\Components\TextInput::make('uid')
                            ->hint('Device ID')
                            ->label('UID')
                            ->validationAttribute('uid')
                            ->numeric()
                            ->type('text')
                            ->unique(ignoreRecord: true)
                            ->rules(['required', 'min:2', 'max:255'])
                            ->markAsRequired()
                            ->dehydrateStateUsing(fn (?string $state): ?int => (int) $state),
                        Forms\Components\Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Printout Configuration')
                    ->columns()
                    ->schema([
                        Forms\Components\ColorPicker::make('print.foreground_color')
                            ->rgba()
                            ->label('Foreground Color')
                            ->default('rgba(0, 0, 0, 1)')
                            ->helperText('The color of the text in the printout.'),
                        Forms\Components\ColorPicker::make('print.background_color')
                            ->rgba()
                            ->label('Background Color')
                            ->default('rgba(0, 0, 0, 0)')
                            ->helperText('The color of the background in the printout.'),
                    ]),
                Forms\Components\Section::make('Connection Parameters')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('host')
                            ->requiredWith('port')
                            ->helperText('The hostname or IP address of the scanner.'),
                        Forms\Components\TextInput::make('port')
                            ->numeric()
                            ->type('text')
                            ->helperText('The port number of the scanner.'),
                        Forms\Components\TextInput::make('pass')
                            ->password()
                            ->helperText('The password of the scanner.'),
                    ]),
            ]);
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
                    ->searchable(query: fn ($query, $search) => $query->whereRaw("CAST(uid as TEXT) = $search")),
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
