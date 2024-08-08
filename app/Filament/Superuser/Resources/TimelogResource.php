<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\TimelogMode;
use App\Enums\TimelogState;
use App\Filament\Superuser\Resources\TimelogResource\Pages;
use App\Models\Employee;
use App\Models\Timelog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->placeholder('Unknown')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->searchable(query: fn ($query, $search) => $query->whereUid($search)),
                Tables\Columns\TextColumn::make('time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
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

                        if (isset($data['from'])) {
                            $indicators[] = Indicator::make('From: '.Carbon::parse($data['from'])->format('Y-m-d H:i'))
                                ->removeField('from');
                        }

                        if (isset($data['until'])) {
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
                Tables\Filters\Filter::make('employee')
                    ->form([
                        Select::make('employee')
                            ->getSearchResultsUsing(
                                function (?string $search): array {
                                    return Employee::query()
                                        ->when($search, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
                                        ->limit(20)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => Employee::find($value)?->name)
                            ->searchable()
                            ->multiple()
                            ->preload(),
                    ])
                    ->query(function ($query, array $data) {
                        $query->when($data['employee'], fn ($query, $id) => $query->whereHas('employee', fn ($q) => $q->whereIn('employees.id', $id)));
                    })
                    ->indicateUsing(function (array $data) {
                        if (empty($data['employee'])) {
                            return;
                        }

                        $employees = Employee::select('name')
                            ->find($data['employee'])
                            ->pluck('name');

                        return Indicator::make('Employee: '.$employees->join(' & '))
                            ->removeField('employee');
                    }),
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
            ])
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ])
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
