<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\HolidayType;
use App\Filament\Superuser\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'gmdi-free-cancellation-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->live()
                    ->columnSpanFull()
                    ->rule('required')
                    ->markAsRequired()
                    ->options(HolidayType::class)
                    ->default(HolidayType::REGULAR_HOLIDAY),
                Forms\Components\DatePicker::make('date')
                    ->live()
                    ->columnSpanFull()
                    ->markAsRequired()
                    ->rule('required'),
                Forms\Components\TimePicker::make('from')
                    ->columnSpanFull()
                    ->seconds(false)
                    ->visible(fn (Forms\Get $get) => $get('type') === HolidayType::WORK_SUSPENSION || $get('type') === HolidayType::WORK_SUSPENSION->value),
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->rule('required')
                    ->markAsRequired()
                    ->maxLength(255),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->rows(5),
                Forms\Components\TextInput::make('password')
                    ->columnSpanFull()
                    ->password()
                    ->currentPassword()
                    ->rule('required')
                    ->markAsRequired()
                    ->visible(function (?Holiday $record, Forms\Get $get) {
                        if ($record?->date->lt(now())) {
                            return true;
                        }

                        if (is_null($get('date'))) {
                            return false;
                        }

                        return Carbon::parse($get('date'))->lt(now());
                    }),
                Forms\Components\Hidden::make('created_by')
                    ->default(Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->formatStateUsing(fn (?string $state) => Carbon::parse($state)->format('jS F Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
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
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->requiresConfirmation()
                    ->modalDescription('Modifying past holidays or suspensions (from or to) will require you to enter your password as this may have an irreversible side-effect.')
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(function (?Holiday $record) {
                        $needsPassword = now()->isAfter($record->date);

                        $confirmation = 'This date has already passed. This action will have an irreversible effect. <br>';

                        return str(($needsPassword ? $confirmation : '').'Are you sure you would like to do this?')
                            ->toHtmlString();
                    })
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->currentPassword()
                            ->rules(['required'])
                            ->visible(fn (Holiday $record) => $record->recurring || now()->isAfter($record->date)),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordAction(null)
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListSuspensions::route('/'),
        ];
    }
}
