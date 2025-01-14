<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\BackupResource\Pages;
use App\Models\Backup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('size')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Number::fileSize($state, 0, 3))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('file')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('exception')
                    ->visible(fn ($record) => $record->exception)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->modalContent(fn ($record) => str($record->exception)->toHtmlString())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->visible(fn ($record) => $record->stored)
                    ->action(fn ($record) => response()->download($record->path))
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to download the backup file?')
                    ->modalIcon('heroicon-o-archive-box-arrow-down')
                    ->modalSubmitActionLabel('Download'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackups::route('/'),
        ];
    }
}
