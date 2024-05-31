<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\Scanner;
use App\Models\User;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'gmdi-monitor-heart-o';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activitable_type')
                    ->label('Resource')
                    ->getStateUsing(fn (Activity $record) => class_basename($record->activitable::class))
                    ->searchable(),
                Tables\Columns\TextColumn::make('activitable.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data.action')
                    ->label('Action')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activitable_type')
                    ->options([
                        Scanner::class => 'Scanner',
                        User::class => 'User',
                    ])
                    ->label('Resource')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist([
                        Infolists\Components\KeyValueEntry::make('data')
                            ->hiddenLabel()
                            ->keyLabel('Identifier')
                            ->valueLabel('Data')
                            ->getStateUsing(function (Activity $record) {
                                return [
                                    'time' => $record->time,
                                    'type' => @class_basename($record->activitable_type) ?? 'Data import',
                                    'name' => $record->activitable?->name,
                                    'user' => "{$record->user->name} ({$record->user->email})",
                                    ...$record->data,
                                ];
                            }),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
