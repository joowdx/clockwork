<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use App\Filament\Filters\ActiveFilter;
use App\Filament\Superuser\Resources\EmployeeResource\Pages;
use App\Filament\Superuser\Resources\EmployeeResource\RelationManagers\GroupsRelationManager;
use App\Filament\Superuser\Resources\EmployeeResource\RelationManagers\OfficesRelationManager;
use App\Filament\Superuser\Resources\EmployeeResource\RelationManagers\ScannersRelationManager;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'gmdi-badge-o';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function formSchema(bool $compact = false): array
    {
        $isCalledBySelf = @debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['class'] === get_called_class();

        return [
            Forms\Components\Section::make('Personal Information')
                ->compact($compact)
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('last_name')
                        ->helperText('Family name or surname of the employee.')
                        ->minLength(2)
                        ->markAsRequired()
                        ->rules('required')
                        ->rule(fn (?Employee $record, Get $get) => function ($attribute, $value, $fail) use ($get, $record) {
                            $employee = Employee::withoutGlobalScopes()
                                ->whereNot('id', $record?->id)
                                ->where([
                                    'last_name' => $get('last_name'),
                                    'first_name' => $get('first_name'),
                                ])->when($get('middle_name') === 'N/A', function ($query) {
                                    return $query->where(function ($query) {
                                        $query->where('middle_name', '')
                                            ->orWhereNull('middle_name');
                                    });
                                }, function ($query) use ($get) {
                                    return $query->where('middle_name', $get('middle_name'));
                                })->when($get('qualifier_name') === 'N/A', function ($query) {
                                    return $query->where(function ($query) {
                                        $query->where('qualifier_name', '')
                                            ->orWhereNull('qualifier_name');
                                    });
                                }, function ($query) use ($get) {
                                    return $query->where('qualifier_name', $get('qualifier_name'));
                                });

                            if ($employee->exists()) {
                                $fail('This exact employee already exists.');
                            }
                        }),
                    Forms\Components\TextInput::make('first_name')
                        ->helperText('Given name or forename of the employee.')
                        ->minLength(2)
                        ->markAsRequired()
                        ->rules('required')
                        ->rule(fn (?Employee $record, Get $get) => function ($attribute, $value, $fail) use ($get, $record) {
                            $employee = Employee::withoutGlobalScopes()
                                ->whereNot('id', $record?->id)
                                ->where([
                                    'last_name' => $get('last_name'),
                                    'first_name' => $get('first_name'),
                                ])->when($get('middle_name') === 'N/A', function ($query) {
                                    return $query->where(function ($query) {
                                        $query->where('middle_name', '')
                                            ->orWhereNull('middle_name');
                                    });
                                }, function ($query) use ($get) {
                                    return $query->where('middle_name', $get('middle_name'));
                                })->when($get('qualifier_name') === 'N/A', function ($query) {
                                    return $query->where(function ($query) {
                                        $query->where('qualifier_name', '')
                                            ->orWhereNull('qualifier_name');
                                    });
                                }, function ($query) use ($get) {
                                    return $query->where('qualifier_name', $get('qualifier_name'));
                                });

                            if ($employee->exists()) {
                                $fail('This exact employee already exists.');
                            }
                        }),
                    Forms\Components\TextInput::make('middle_name')
                        ->helperText('Middle name or additional name of the employee usually derived from the mother\'s maiden name.')
                        ->visibleOn('view'),
                    Forms\Components\TextInput::make('middle_name')
                        ->helperText('Middle name or additional name of the employee usually derived from the mother\'s maiden name.')
                        ->hintAction(
                            Forms\Components\Actions\Action::make('na')
                                ->label('n/a')
                                ->icon('heroicon-o-no-symbol')
                                ->extraAttributes(['tabindex' => -1])
                                ->action(fn (Forms\Set $set) => $set('middle_name', 'N/A'))
                        )
                        ->dehydrateStateUsing(fn ($state) => $state === 'N/A' ? '' : $state)
                        ->markAsRequired()
                        ->rules('required')
                        ->hiddenOn('view')
                        ->minLength(2),
                    Forms\Components\Select::make('qualifier_name')
                        ->helperText('Qualifier name or name extension to distinguish an individual from others who may have the same name.')
                        ->options([
                            'N/A' => 'N/A',
                            'Jr.' => 'Jr.',
                            'Sr.' => 'Sr.',
                            'II' => 'II',
                            'III' => 'III',
                            'IV' => 'IV',
                            'V' => 'V',
                            'VI' => 'VI',
                            'VII' => 'VII',
                            'VIII' => 'VIII',
                            'IX' => 'IX',
                            'X' => 'X',
                        ])
                        ->in(['N/A', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'])
                        ->hintAction(
                            Forms\Components\Actions\Action::make('na')
                                ->label('n/a')
                                ->icon('heroicon-o-no-symbol')
                                ->extraAttributes(['tabindex' => -1])
                                ->action(fn (Forms\Set $set) => $set('qualifier_name', 'N/A'))
                        )
                        ->dehydrateStateUsing(fn ($state) => $state === 'N/A' ? '' : $state)
                        ->markAsRequired()
                        ->rules('required'),
                    Forms\Components\DatePicker::make('birthdate'),
                    Forms\Components\Select::make('sex')
                        ->options(['male' => 'Male', 'female' => 'Female']),
                ]),
            Forms\Components\Section::make('Employment Details')
                ->compact($compact)
                ->columns(6)
                ->schema([
                    Forms\Components\TextInput::make('designation')
                        // ->requiredWith('status')
                        ->columnSpan(2),
                    Forms\Components\Select::make('status')
                        ->options(EmploymentStatus::class)
                        ->afterStateUpdated(fn (callable $set) => $set('substatus', ''))
                        ->dehydrateStateUsing(fn ($state) => empty(trim($state)) ? '' : $state)
                        ->disableOptionWhen(fn (string $value) => $value === EmploymentStatus::NONE->value)
                        ->columns(3)
                        ->searchable()
                        ->columnSpan(2)
                        ->live(),
                    Forms\Components\Select::make('substatus')
                        ->options(EmploymentSubstatus::class)
                        ->requiredIf('status', EmploymentStatus::CONTRACTUAL->value)
                        ->prohibitedUnless('status', EmploymentStatus::CONTRACTUAL->value)
                        ->disableOptionWhen(fn (string $value) => $value === EmploymentSubstatus::NONE->value)
                        ->hidden(fn (Forms\Get $get) => $get('status') !== EmploymentStatus::CONTRACTUAL->value)
                        ->dehydrateStateUsing(fn ($state) => empty(trim($state)) ? '' : $state)
                        ->dehydratedWhenHidden()
                        ->searchable()
                        ->columnSpan(2),
                ]),
            Forms\Components\Section::make('Account Settings')
                ->visible($isCalledBySelf)
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('uid')
                        ->columnSpan(2)
                        ->visibleOn('view'),
                    Forms\Components\TextInput::make('uid')
                        ->columnSpan(2)
                        ->label('UID')
                        ->helperText('This eight character UID will used to uniquely identify the employee across interconnected systems.')
                        ->minLength(8)
                        ->maxLength(8)
                        ->readOnly()
                        ->alphaNum()
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                        ->unique(ignoreRecord: true)
                        ->hiddenOn('view')
                        ->hintAction(
                            Forms\Components\Actions\Action::make('generate')
                                ->label('Generate')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function (Forms\Set $set) {
                                    $valid = function (string $uid): bool {
                                        return Employee::whereUid($uid)->doesntExist();
                                    };

                                    $set('uid', strtoupper(fake()->valid($valid)->bothify('?????###')));
                                })
                        ),
                    Forms\Components\ToggleButtons::make('active')
                        ->boolean()
                        ->inline()
                        ->grouped()
                        ->required()
                        ->default(true),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->formatStateUsing(function (Employee $record) {
                        $offices = $record->offices->map(function ($office) {
                            return str($office->code)
                                ->when($office->pivot->current, function ($code) {
                                    return <<<HTML
                                        <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--primary-400);--c-600:var(--primary-600);">$code</span>
                                    HTML;
                                });
                        })->join(', ');

                        return str($offices)->toHtmlString();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->getStateUsing(function (Employee $employee): string {
                        return str($employee->status?->value)
                            ->title()
                            ->when($employee->substatus?->value, function ($status) use ($employee) {
                                return $status->append(" ({$employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('undeployed')
                    ->attribute('office_id')
                    ->trueLabel('No')
                    ->falseLabel('Yes')
                    ->nullable()
                    ->native(false)
                    ->queries(
                        fn ($query) => $query->whereHas('offices'),
                        fn ($query) => $query->whereDoesntHave('offices'),
                    ),
                Tables\Filters\SelectFilter::make('offices')
                    ->multiple()
                    ->searchable()
                    ->relationship('offices', 'code')
                    ->preload(),
                Tables\Filters\SelectFilter::make('groups')
                    ->multiple()
                    ->searchable()
                    ->relationship('groups', 'name')
                    ->preload(),
                ActiveFilter::make(),
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('Set active state')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->groupedIcon('heroicon-o-check-circle')
                        ->form([
                            Forms\Components\Section::make([
                                Forms\Components\Radio::make('active')
                                    ->boolean()
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->required(),
                            ]),
                        ])
                        ->action(function (Tables\Actions\BulkAction $action, Collection $records, array $data) {
                            $records->toQuery()->update(['active' => $data['active']]);

                            $action->deselectRecordsAfterCompletion();

                            $label = $records->count() > 1 ? static::getPluralModelLabel() : static::getModelLabel();

                            Notification::make()
                                ->success()
                                ->title('Active state updated')
                                ->body($records->count()." $label has been set to ".($data['active'] ? 'active' : 'inactive').'.')
                                ->send();
                        }),
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
            OfficesRelationManager::class,
            ScannersRelationManager::class,
            GroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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
