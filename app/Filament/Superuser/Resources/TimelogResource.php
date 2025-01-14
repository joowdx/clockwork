<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\TimelogMode;
use App\Enums\TimelogState;
use App\Filament\Superuser\Resources\TimelogResource\Pages;
use App\Models\Timelog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class TimelogResource extends Resource
{
    protected static ?string $model = Timelog::class;

    protected static ?string $navigationIcon = 'gmdi-alarm-on-o';

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
                Tables\Columns\TextColumn::make('scanner.name')
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono']),
                Tables\Columns\TextColumn::make('employee.name')
                    ->placeholder('Unknown')
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->searchable(query: fn ($query, $search) => $query->whereUid($search), isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('time')
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono']),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\TextColumn::make('mode'),
            ])
            ->filters([
                Tables\Filters\Filter::make('time')
                    ->columnSpanFull()
                    ->columns(2)
                    ->form([
                        DateTimePicker::make('from')
                            ->seconds(false),
                        DateTimePicker::make('until')
                            ->seconds(false),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when($data['from'], fn ($q, $d) => $q->where('time', '>=', Carbon::parse($d)->format('Y-m-d H:i:s')));
                        $query->when($data['until'], fn ($q, $d) => $q->where('time', '<=', Carbon::parse($d)->format('Y-m-d H:i:s')));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (isset($data['from']) && ! empty($data['from'])) {
                            $indicators[] = Indicator::make('From: '.Carbon::parse($data['from'])->format('Y-m-d H:i'))
                                ->removeField('from');
                        }

                        if (isset($data['until']) && ! empty($data['until'])) {
                            $indicators[] = Indicator::make('Until: '.Carbon::parse($data['until'])->format('Y-m-d H:i'))
                                ->removeField('until');
                        }

                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('scanner')
                    ->relationship('scanner', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('mode')
                    ->options(TimelogMode::class)
                    ->multiple()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('state')
                    ->options(TimelogState::class)
                    ->multiple()
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('unknown')
                    ->label('Unknown')
                    ->placeholder('All')
                    ->trueLabel('Records from unknown')
                    ->falseLabel('Records with enrollments')
                    ->queries(
                        fn ($query) => $query->whereDoesntHave('employee'),
                        fn ($query) => $query->whereHas('employee'),
                    )
                    ->native(false),
            ], FiltersLayout::AboveContent)
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ])
            ->deferLoading()
            ->defaultSort('time', 'desc')
            ->filtersFormWidth(MaxWidth::ThreeExtraLarge)
            ->filtersFormColumns(2);
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
            'index' => Pages\ListTimelogs::route('/'),
        ];
    }
}
