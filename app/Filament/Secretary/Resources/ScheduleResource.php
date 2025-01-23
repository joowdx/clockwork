<?php

namespace App\Filament\Secretary\Resources;

use App\Enums\RequestStatus;
use App\Filament\Actions\Request\TableActions\CancelAction;
use App\Filament\Actions\Request\TableActions\ShowRoutingAction;
use App\Filament\Filters\RequestStatusFilter;
use App\Filament\Secretary\Resources\ScheduleResource\Pages;
use App\Filament\Secretary\Resources\ScheduleResource\RelationManagers\EmployeesRelationManager;
use App\Filament\Superuser\Resources\ScheduleResource as SuperuserScheduleResource;
use App\Models\Schedule;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'gmdi-punch-clock-o';

    public static function form(Form $form): Form
    {
        return SuperuserScheduleResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->visible(settings('requests'))
                    ->searchable()
                    ->getStateUsing(fn (Schedule $record) => $record->drafted ? null : ($record->request->cancelled ? null : $record->title))
                    ->placeholder(fn (Schedule $record) => $record->drafted ? 'Drafted' : ($record->request->cancelled ? 'Cancelled' : $record->title)),
                Tables\Columns\TextColumn::make('period')
                    ->extraCellAttributes(['class' => 'font-mono']),
                Tables\Columns\TextColumn::make('days')
                    ->label('Days')
                    ->formatStateUsing(function (Schedule $record): string {
                        return match ($record->days) {
                            'everyday' => 'Everyday',
                            'weekday' => 'Weekdays',
                            // 'holiday' => 'Holiday',
                            'weekend' => 'Weekends',
                        };
                    }),
                Tables\Columns\TextColumn::make('request.status')
                    ->visible(settings('requests'))
                    ->placeholder('Draft'),
                Tables\Columns\TextColumn::make('request.user.name')
                    ->label('Requestor'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                RequestStatusFilter::make(),
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn (?Schedule $record) => ! in_array($record?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN])),
                Tables\Actions\EditAction::make()
                    ->hidden(fn (?Schedule $record) => ! in_array($record?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN])),
                Tables\Actions\ActionGroup::make([
                    ShowRoutingAction::make(),
                    CancelAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->recordUrl(null)
            ->deferLoading();
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            // 'create-overtime' => Pages\CreateOvertimeSchedule::route('/create-overtime'),
            'view' => Pages\ViewSchedule::route('/{record}/view'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $offices = user()->offices?->pluck('id')->toArray();

        return parent::getEloquentQuery()
            ->whereNot('global', true)
            ->when(count($offices), fn ($q) => $q->whereIn('office_id', empty($offices) ? [] : $offices))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
