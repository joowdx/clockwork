<?php

namespace App\Filament\Secretary\Resources;

use App\Enums\RequestStatus;
use App\Enums\WorkArrangement;
use App\Filament\Actions\Request\TableActions\CancelAction;
use App\Filament\Actions\Request\TableActions\ShowRoutingAction;
use App\Filament\Secretary\Resources\ScheduleResource\Pages;
use App\Filament\Secretary\Resources\ScheduleResource\RelationManagers\EmployeesRelationManager;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'gmdi-punch-clock-o';

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(fn (?Schedule $record) => ! in_array($record?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN]))
            ->schema([
                Forms\Components\Section::make('Schedule Period')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                Forms\Components\ToggleButtons::make('days')
                                    ->label('Days')
                                    ->options([
                                        'everyday' => 'Everyday',
                                        'weekday' => 'Weekday',
                                        // 'holiday' => 'Holiday',
                                        'weekend' => 'Weekend',
                                    ])
                                    ->default('everyday')
                                    ->required()
                                    ->dehydratedWhenHidden()
                                    ->columns(3)
                                    ->rules([
                                        fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                                            if (empty($get('start')) || empty($get('end'))) {
                                                return;
                                            }

                                            if (in_array($value, ['everyday', 'holiday'])) {
                                                return;
                                            }

                                            $days = LazyCollection::make(fn () => yield from Carbon::parse($get('start'))->range($get('end')));

                                            if ($days->filter->{'is'.ucfirst($value)}()->isEmpty()) {
                                                $fail("Invalid selection. Selected range do not have a $value.");
                                            }
                                        },
                                    ]),
                            ]),
                        Forms\Components\Select::make('office_id')
                            ->relationship('office', 'name', fn ($query) => $query->whereIn('id', auth()->user()->offices->pluck('id')->toArray()))
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::UNSET->value)
                            ->columnSpan(2)
                            ->dehydratedWhenHidden(),
                        Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start')
                                    ->markAsRequired()
                                    ->rules([
                                        'required',
                                        fn (Forms\Get $get) => function (string $attribute, string $value, \Closure $fail) use ($get) {
                                            if (empty($get('office_id')) || empty($get('end'))) {
                                                return;
                                            }

                                            if (Carbon::parse($value)->year !== Carbon::parse($get('end'))->year) {
                                                $fail('The start and end dates must be within the same year.');
                                            }
                                        },
                                    ]),
                                Forms\Components\DatePicker::make('end')
                                    ->after('start')
                                    ->markAsRequired()
                                    ->rules([
                                        'required',
                                        fn (Forms\Get $get) => function (string $attribute, string $value, \Closure $fail) use ($get) {
                                            if (empty($get('office_id')) || empty($get('start'))) {
                                                return;
                                            }

                                            if (Carbon::parse($get('start'))->year !== Carbon::parse($value)->year) {
                                                $fail('The start and end dates must be within the same year.');
                                            }
                                        },
                                    ]),
                            ]),
                        Forms\Components\Radio::make('arrangement')
                            ->label('Arrangement')
                            ->options(WorkArrangement::class)
                            ->validationMessages(['not_in' => 'This feature is not yet supported or deprecated and might be removed in the future.'])
                            ->columnSpan(2)
                            ->live()
                            ->markAsRequired()
                            ->default(WorkArrangement::STANDARD_WORK_HOUR->value)
                            ->dehydrated(fn (?string $state) => isset($state)),
                    ]),
                Forms\Components\Section::make('Timetable')
                    ->hidden(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::UNSET->value)
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        Forms\Components\Fieldset::make('Entirety')
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\TextInput::make('timetable.duration')
                                    ->label('Duration')
                                    ->hint('hrs')
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('The total number of work hours for the day.')
                                    ->default(8)
                                    ->markAsRequired()
                                    ->rules(['numeric', 'required', 'min:6', 'max:12']),
                                Forms\Components\TextInput::make('timetable.break')
                                    ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                    ->label('Break')
                                    ->hint('mins')
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('The total number of minutes for the break between Punch 2 and Punch 3 and are not included in the total work hours.')
                                    ->default(60)
                                    ->markAsRequired()
                                    ->rules(['numeric', 'required', 'min:0', 'max:180']),
                            ]),
                        Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->visible(function (Forms\Get $get) {
                                return in_array($get('arrangement'), [
                                    WorkArrangement::STANDARD_WORK_HOUR->value,
                                    // WorkArrangement::FLEXI_TIME->value,
                                ]);
                            })
                            ->schema([
                                Forms\Components\Fieldset::make('AM')
                                    ->schema([
                                        Forms\Components\TimePicker::make('timetable.p1')
                                            ->rules(['required'])
                                            ->markAsRequired()
                                            ->seconds(false)
                                            ->default('08:00')
                                            ->label('Punch 1')
                                            ->hint('in')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Employees must "clock in" before the specified time. Otherwise, the attendance record will be disregarded.')
                                            ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                            ->rules([
                                                'date_format:H:i',
                                                fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    $min = '04:00';

                                                    if ($value < $min) {
                                                        $fail("The punch 1 field must be a time before $min.");
                                                    }

                                                    if (empty($get('timetable.duration'))) {
                                                        return;
                                                    }

                                                    $p1p2 = today()->setTime(...explode(':', $get('timetable.p1')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p2'))));

                                                    $p3p4 = today()->setTime(...explode(':', $get('timetable.p3')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p4'))));

                                                    if (($total = (int) $p1p2 + $p3p4) != (int) $get('timetable.duration')) {
                                                        $fail("The total number of work hours ({$total}) must be equal to the work hour duration (".$get('timetable.duration').').');
                                                    }
                                                },
                                            ]),
                                        Forms\Components\TimePicker::make('timetable.p2')
                                            ->rules(['required'])
                                            ->markAsRequired()
                                            ->seconds(false)
                                            ->default('12:00')
                                            ->label('Punch 2')
                                            ->hint('out')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Employees must "clock out" within the specified time. Otherwise, the attendance record will be disregarded.')
                                            ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                            ->rules([
                                                'date_format:H:i',
                                                fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    if ($value <= $get('timetable.p1')) {
                                                        $fail('The punch 2 field must be a time after the punch 1 field.');
                                                    }

                                                    if (today()->setTime(...explode(':', $get('timetable.p1')))->diffInMinutes(today()->setTime(...explode(':', $value))) < 60) {
                                                        $fail('The punch 2 field must be at least an hour after the punch 1 field.');
                                                    }

                                                    if (empty($get('timetable.duration'))) {
                                                        return;
                                                    }

                                                    $p1p2 = today()->setTime(...explode(':', $get('timetable.p1')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p2'))));

                                                    $p3p4 = today()->setTime(...explode(':', $get('timetable.p3')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p4'))));

                                                    if (($total = (int) $p1p2 + $p3p4) != (int) $get('timetable.duration')) {
                                                        $fail("The total number of work hours ({$total}) must be equal to the work hour duration (".$get('timetable.duration').').');
                                                    }
                                                },
                                            ]),
                                    ]),
                                Forms\Components\Fieldset::make('PM')
                                    ->schema([
                                        Forms\Components\TimePicker::make('timetable.p3')
                                            ->rules(['required'])
                                            ->markAsRequired()
                                            ->seconds(false)
                                            ->default('13:00')
                                            ->label('Punch 3')
                                            ->hint('in')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Employees must "clock in" within the specified time. Otherwise, the attendance record will be disregarded.')
                                            ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                            ->rules([
                                                'date_format:H:i',
                                                fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    if ($value <= $get('timetable.p2')) {
                                                        $fail('The punch 3 field must be a time after the punch 2 field.');
                                                    }

                                                    if (empty($get('timetable.break'))) {
                                                        return;
                                                    }

                                                    if (today()->setTime(...explode(':', $get('timetable.p2')))->diffInMinutes(today()->setTime(...explode(':', $value))) != $break = $get('timetable.break')) {
                                                        $fail("The punch 3 field must be $break minutes after the punch 2 field.");
                                                    }

                                                    if (empty($get('timetable.duration'))) {
                                                        return;
                                                    }

                                                    $p1p2 = today()->setTime(...explode(':', $get('timetable.p1')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p2'))));

                                                    $p3p4 = today()->setTime(...explode(':', $get('timetable.p3')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p4'))));

                                                    if (($total = (int) $p1p2 + $p3p4) != (int) $get('timetable.duration')) {
                                                        $fail("The total number of work hours ({$total}) must be equal to the work hour duration (".$get('timetable.duration').').');
                                                    }
                                                },
                                            ]),
                                        Forms\Components\TimePicker::make('timetable.p4')
                                            ->rules(['required'])
                                            ->markAsRequired()
                                            ->seconds(false)
                                            ->default('17:00')
                                            ->label('Punch 4')
                                            ->hint('out')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Employees must "clock out" within the specified time. Otherwise, the attendance record will be disregarded.')
                                            ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                            ->rules([
                                                'date_format:H:i',
                                                fn (Forms\Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    $max = '22:00';

                                                    if ($value > $max) {
                                                        $fail("The punch 4 field must be a time after $max.");
                                                    }

                                                    if ($value <= $get('timetable.p3')) {
                                                        $fail('The punch 4 field must be a time after the punch 3 field.');
                                                    }

                                                    if (today()->setTime(...explode(':', $get('timetable.p3')))->diffInMinutes(today()->setTime(...explode(':', $value))) < 60) {
                                                        $fail('The punch 4 field must be at least an hour after the punch 3 field.');
                                                    }

                                                    if (empty($get('timetable.duration'))) {
                                                        return;
                                                    }

                                                    $p1p2 = today()->setTime(...explode(':', $get('timetable.p1')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p2'))));

                                                    $p3p4 = today()->setTime(...explode(':', $get('timetable.p3')))->diffInHours(today()->setTime(...explode(':', $get('timetable.p4'))));

                                                    if (($total = (int) $p1p2 + $p3p4) != (int) $get('timetable.duration')) {
                                                        $fail("The total number of work hours ({$total}) must be equal to the work hour duration (".$get('timetable.duration').').');
                                                    }
                                                },
                                            ]),

                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
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
                    ->placeholder('Draft'),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            // 'create-overtime' => Pages\CreateOvertimeSchedule::route('/create-overtime'),
            'view' => Pages\ViewSchedule::route('/{record}/view'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $offices = auth()->user()->offices?->pluck('id')->toArray();

        return parent::getEloquentQuery()
            ->whereNot('global', true)
            ->when(count($offices), fn ($q) => $q->whereIn('office_id', empty($offices) ? [] : $offices));
    }
}
