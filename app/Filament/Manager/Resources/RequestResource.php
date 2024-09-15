<?php

namespace App\Filament\Manager\Resources;

use App\Enums\RequestStatus;
use App\Filament\Actions\Request\TableActions\RespondAction;
use App\Filament\Manager\Resources\RequestResource\Pages;
use App\Models\Request;
use App\Models\Schedule;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'gmdi-rule-folder-o';

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
            ->columns([
                Tables\Columns\TextColumn::make('requestable')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => class_basename($record->requestable)),
                Tables\Columns\TextColumn::make('requestable.title')
                    ->label('Title')
                    ->searchable()
                    ->getStateUsing(fn (Request $record) => $record->requestable->title),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('requestable.requestor.name')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('to')
                    ->label('Target')
                    ->getStateUsing(fn (Request $record) => ucfirst($record->to))
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('requestable.requested_at')
                    ->label('Time')
                    ->placeholder(fn ($record) => $record->created_at),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('requestable_type')
                    ->label('Type')
                    ->native(false)
                    ->options([
                        Schedule::class => 'Schedule',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(RequestStatus::class)
                    ->default([RequestStatus::REQUEST->value, RequestStatus::DEFLECT->value])
                    ->multiple()
                    ->native(false),
                // Tables\Filters\SelectFilter::make('for')
                //     ->options(['approval' => 'Approval', 'revokation' => 'Revokation', 'termination' => 'Termination'])
                //     ->multiple()
                //     ->native(false),
                Tables\Filters\SelectFilter::make('completed')
                    ->options([true => 'Completed', false => 'Pending'])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->modalHeading(fn (Request $record) => $record->requestable->title)
                    ->modalContent(fn (Request $record) => view('filament.requests.view', ['schedule' => $record->requestable, 'request' => $record]))
                    ->modalCancelActionLabel('Close')
                    ->modalSubmitAction(false)
                    ->modalWidth('2xl')
                    ->slideOver(),
                RespondAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
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
            'index' => Pages\ListRequests::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNot('status', RequestStatus::CANCEL);

                    $query->whereIn('id', Request::selectRaw('MAX(requests.id)')->groupBy('requestable_id', 'requestable_type'));
                });

                $query->orWhere('completed', true);
            })
            ->whereHas('requestable');
    }
}
