<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\RequestStatus;
use App\Enums\WorkArrangement;
use App\Filament\Superuser\Resources\ScheduleResource\Pages;
use App\Filament\Superuser\Resources\ScheduleResource\RelationManagers\EmployeesRelationManager;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\LazyCollection;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'gmdi-punch-clock-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Period')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                Forms\Components\ToggleButtons::make('global')
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('Global schedules will be applied to all employees without specific schedules.')
                                    ->required()
                                    ->boolean()
                                    ->inline()
                                    ->grouped()
                                    ->default(true)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, string $state) {
                                        if ($state) {
                                            $set('arrangement', WorkArrangement::STANDARD_WORK_HOUR->value);

                                            $set('office_id', null);
                                        }
                                    }),
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
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Forms\Get $get) => $get('global') || $get('arrangement') == WorkArrangement::UNSET->value)
                            ->required(fn (Forms\Get $get) => ! $get('global'))
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
                            ->disableOptionWhen(function (string $value, Forms\Get $get) {
                                return match ($value) {
                                    WorkArrangement::UNSET->value,
                                    WorkArrangement::WORK_SHIFTING->value,
                                    WorkArrangement::COMPRESSED_WORK_WEEK->value,
                                    WorkArrangement::ROUND_THE_CLOCK->value => $get('global'),
                                    default => false,
                                };
                            })
                            ->columnSpan(2)
                            ->columns(2)
                            ->live()
                            ->markAsRequired()
                            ->default(WorkArrangement::STANDARD_WORK_HOUR->value)
                            ->dehydrated(fn (?string $state) => isset($state))
                            ->rules([
                                'required',
                                fn (Forms\Get $get) => function (string $attribute, string $value, \Closure $fail) use ($get) {
                                    if ($get('global') && ! in_array($value, [WorkArrangement::STANDARD_WORK_HOUR->value/**WorkArrangement::FLEXI_TIME->value*/])) {
                                        $fail('Global schedules can only have standard work hours.');
                                    }
                                },
                            ]),
                    ]),
                Forms\Components\Section::make('Timetable')
                    ->hidden(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::UNSET->value)
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Fieldset::make('Entirety')
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
                Forms\Components\Section::make('Threshold')
                    ->hidden(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::UNSET->value)
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Punch 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('threshold.p1.min')
                                            ->label('Min')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "before" the specified number of minutes from Punch 1.')
                                            ->default(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value ? 280 : 120)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                        Forms\Components\TextInput::make('threshold.p1.max')
                                            ->label('Max')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "after" the specified number of minutes from Punch 1.')
                                            ->default(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value ? 180 : 360)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                    ]),
                                Forms\Components\Fieldset::make('Punch 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('threshold.p2.min')
                                            ->label('Min')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "before" the specified number of minutes from Punch 2.')
                                            ->default(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value ? 180 : 360)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                        Forms\Components\TextInput::make('threshold.p2.max')
                                            ->label('Max')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "after" the specified number of minutes from Punch 2.')
                                            ->default(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value ? 120 : 420)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                    ]),
                                Forms\Components\Fieldset::make('Punch 3')
                                    ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                    ->schema([
                                        Forms\Components\TextInput::make('threshold.p3.min')
                                            ->label('Min')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "before" the specified number of minutes from Punch 3.')
                                            ->default(120)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                        Forms\Components\TextInput::make('threshold.p3.max')
                                            ->label('Max')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "after" the specified number of minutes from Punch 3.')
                                            ->default(180)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                    ]),
                                Forms\Components\Fieldset::make('Punch 4')
                                    ->visible(fn (Forms\Get $get) => $get('arrangement') == WorkArrangement::STANDARD_WORK_HOUR->value)
                                    ->schema([
                                        Forms\Components\TextInput::make('threshold.p4.min')
                                            ->label('Min')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "before" the specified number of minutes from Punch 4.')
                                            ->default(180)
                                            ->numeric()
                                            ->markAsRequired()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                        Forms\Components\TextInput::make('threshold.p4.max')
                                            ->label('Max')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('Disregard attendance records that are "after" the specified number of minutes from Punch 4.')
                                            ->default(360)
                                            ->numeric()
                                            ->type('text')
                                            ->markAsRequired()
                                            ->rules(['required', 'min:0']),
                                    ]),
                                Forms\Components\Fieldset::make('Miscellaneous')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('threshold.tardy')
                                            ->label('Tardy')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('How many minutes after the punch(in) is considered tardy.')
                                            ->default(0)
                                            ->numeric()
                                            ->type('text'),
                                        Forms\Components\TextInput::make('threshold.overtime')
                                            ->label('Overtime')
                                            ->hint('mins')
                                            ->hintIcon('heroicon-m-question-mark-circle')
                                            ->hintIconTooltip('How many minutes after the last punch(out) is considered overtime.')
                                            ->default(120)
                                            ->numeric()
                                            ->type('text'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                Tables\Columns\TextColumn::make('global')
                    ->label('Office')
                    ->formatStateUsing(function (Schedule $record): HtmlString|string {
                        return $record->office ? $record->office->code : str('Global')->wrap('**')->wrap('(', ')')->inlineMarkdown()->toHtmlString();
                    }),
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
                Tables\Filters\TernaryFilter::make('approved')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        fn ($q) => $q->whereHas('request', fn ($q) => $q->where('status', RequestStatus::APPROVE)->where('for', 'approval')),
                        fn ($q) => $q->whereHas('request', fn ($q) => $q->whereNot('status', RequestStatus::APPROVE)->where('for', 'approval'))
                            ->orWhereDoesntHave('request'),
                    ),
                Tables\Filters\TrashedFilter::make(),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
