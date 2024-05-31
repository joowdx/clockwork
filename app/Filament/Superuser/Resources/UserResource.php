<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\Permissions\DeveloperRolePermission;
use App\Enums\Permissions\EmployeePermission;
use App\Enums\Permissions\SchedulePermission;
use App\Enums\Permissions\SecretaryRolePermission;
use App\Enums\Permissions\UserPermission;
use App\Enums\UserRole;
use App\Filament\Superuser\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'gmdi-supervisor-account-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Information and Credentials')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->columnSpan(2)
                            ->rule('required')
                            ->markAsRequired(),
                        Forms\Components\TextInput::make('email')
                            ->columnSpan(2)
                            ->rule('required')
                            ->markAsRequired(),
                        Forms\Components\TextInput::make('password')
                            ->columnSpan(2)
                            ->password()
                            ->rule(Password::default())
                            ->rule(fn (string $operation) => $operation === 'create' ? 'required' : null)
                            ->markAsRequired(fn (string $operation) => $operation === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn (?string $state) => ! empty($state))
                            ->requiredWith('passwordConfirmation')
                            ->same('passwordConfirmation')
                            ->hiddenOn(['view']),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->columnSpan(2)
                            ->password()
                            ->rule(fn (string $operation) => $operation === 'create' ? 'required' : null)
                            ->markAsRequired(fn (string $operation) => $operation === 'create')
                            ->requiredWith('password')
                            ->dehydrated(false)
                            ->hiddenOn(['view']),
                    ]),
                Forms\Components\Section::make('Employee Profile Link')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->columnSpan(2)
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload(),
                    ]),
                Forms\Components\Section::make('Roles and Permissions')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\CheckboxList::make('roles')
                                ->live()
                                ->bulkToggleable()
                                ->options(function () {
                                    return collect(array_combine(array_column(UserRole::cases(), 'name'), array_column(UserRole::cases(), 'value')))
                                        ->filter(fn ($value) => auth()->user()->root ? true : ! in_array($value, [UserRole::ROOT->value, UserRole::DEVELOPER->value]))
                                        ->mapWithKeys(fn ($value, $name) => [$value => UserRole::tryFrom($value)->getLabel()])
                                        ->toArray();
                                }),
                        ])->columnSpan(1),
                        Forms\Components\Group::make([
                            Forms\Components\Tabs::make('permissions')
                                ->contained(false)
                                ->columnSpanFull()
                                ->schema([
                                    Forms\Components\Tabs\Tab::make('User')
                                        ->visible(fn (Forms\Get $get) => in_array('superuser', $get('roles')))
                                        ->schema([
                                            Forms\Components\CheckboxList::make('permissions')
                                                ->bulkToggleable()
                                                ->columns(2)
                                                ->options(UserPermission::class),
                                        ]),
                                    Forms\Components\Tabs\Tab::make('Employee')
                                        ->visible(fn (Forms\Get $get) => in_array('superuser', $get('roles')))
                                        ->schema([
                                            Forms\Components\CheckboxList::make('permissions')
                                                ->bulkToggleable()
                                                ->columns(2)
                                                ->options(EmployeePermission::class),
                                        ]),
                                    Forms\Components\Tabs\Tab::make('Schedule')
                                        ->visible(fn (Forms\Get $get) => in_array('superuser', $get('roles')))
                                        ->schema([
                                            Forms\Components\CheckboxList::make('permissions')
                                                ->bulkToggleable()
                                                ->columns(2)
                                                ->options(SchedulePermission::class),
                                        ]),
                                    // Forms\Components\Tabs\Tab::make('Scanner')
                                    //     ->schema([
                                    //         Forms\Components\CheckboxList::make('permissions')
                                    //             ->bulkToggleable()
                                    //             ->columns(2)
                                    //             ->options(EmployeePermission::class),
                                    //     ]),
                                    Forms\Components\Tabs\Tab::make('Developer')
                                        ->visible(fn (Forms\Get $get) => in_array('developer', $get('roles')))
                                        ->schema([
                                            Forms\Components\CheckboxList::make('permissions')
                                                ->bulkToggleable()
                                                ->options(DeveloperRolePermission::class),
                                        ]),
                                    Forms\Components\Tabs\Tab::make('Secretary')
                                        ->visible(fn (Forms\Get $get) => in_array('secretary', $get('roles')))
                                        ->schema([
                                            Forms\Components\CheckboxList::make('permissions')
                                                ->bulkToggleable()
                                                ->options(SecretaryRolePermission::class),
                                        ]),
                                ]),
                        ])->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles'),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(null);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNot('id', auth()->id())
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
