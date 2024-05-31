<?php

namespace App\Filament\Developer\Resources;

use App\Enums\UserRole;
use App\Models\Route;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $navigationIcon = 'gmdi-route-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Route information')
                    ->schema([
                        Forms\Components\Select::make('model')
                            ->options([
                                Schedule::class => 'Schedule',
                            ])
                            ->required(),
                        Forms\Components\Repeater::make('path')
                            ->grid(4)
                            ->simple(
                                Forms\Components\Select::make('role')
                                    ->label('Role')
                                    ->options(UserRole::requestable())
                                    ->required(),
                            ),
                        Forms\Components\Repeater::make('escalation')
                            ->grid(4)
                            ->simple(
                                Forms\Components\Select::make('role')
                                    ->label('Role')
                                    ->options(UserRole::requestable())
                                    ->required(),
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn (Route $record) => class_basename($record->model)),
                Tables\Columns\TextColumn::make('path')
                    ->getStateUsing(fn (Route $record) => collect($record->path)->map(fn ($path) => UserRole::tryFrom($path)->getLabel())->join(', ')),
                Tables\Columns\TextColumn::make('escalation')
                    ->getStateUsing(fn (Route $record) => collect($record->escalation)->map(fn ($target) => UserRole::tryFrom($target)->getLabel())->join(', ')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
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
            'index' => RouteResource\Pages\ListRoutes::route('/'),
            'create' => RouteResource\Pages\CreateRoute::route('/create'),
            'edit' => RouteResource\Pages\EditRoute::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
