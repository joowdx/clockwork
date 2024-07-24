<?php

namespace App\Filament\Superuser\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OfficesRelationManager extends RelationManager
{
    protected static string $relationship = 'offices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->attachAnother(false)
                    ->preloadRecordSelect()
                    ->slideOver()
                    ->multiple()
                    ->label('Assign'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove'),
                ]),
            ])
            ->inverseRelationship('users');
    }
}
