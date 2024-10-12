<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Secretary\Resources\GroupResource\Pages;
use App\Filament\Superuser\Resources\GroupResource\RelationManagers\EmployeesRelationManager;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'gmdi-diversity-2-o';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Group name')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->hiddenLabel()
                        ->alphaDash()
                        ->required()
                        ->columnSpanFull()
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (string $state): ?string => mb_strtolower($state)),
                ]),
        ]);
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
                    ->counts(['employees' => function ($query) {
                        $query->where('member.active', true);

                        $query->where(function (Builder $query) {
                            $query->orWhereHas('offices', function (Builder $query) {
                                $query->whereIn('offices.id', user()->offices->pluck('id'));
                            });

                            $query->orWhereHas('scanners', function (Builder $query) {
                                $query->whereIn('scanners.id', user()->scanners->pluck('id'));
                            });
                        });
                    }])
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
            EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where(function ($query) {
                $query->whereHas('employees', function ($query) {
                    $query->where(function ($query) {
                        $query->orWhereHas('scanners', function ($query) {
                            $query->whereIn('scanners.id', user()->scanners->pluck('id'));
                        });

                        $query->orWhereHas('offices', function ($query) {
                            $query->whereIn('offices.id', user()->offices->pluck('id'));
                        });
                    });

                    $query->where('employees.active', true);
                });

                $query->orWhereDoesntHave('employees');
            });
    }
}
