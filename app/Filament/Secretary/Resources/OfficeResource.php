<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Secretary\Resources\OfficeResource\Pages;
use App\Filament\Secretary\Resources\OfficeResource\RelationManagers\EmployeesRelationManager;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'gmdi-corporate-fare-o';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(): array
    {
        $isCalledBySelf = @debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['class'] === get_called_class();

        return [
            Forms\Components\Section::make('General information')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->helperText('The full expanded name of the office.'),
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('The shorthand name of the office.'),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->toggleable(),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ])
            ->recordUrl(null);
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
            'index' => Pages\ListOffices::route('/'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('offices.id', auth()->user()->offices?->pluck('id')->toArray());
    }
}
