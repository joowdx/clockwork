<?php

namespace App\Filament\Superuser\Resources;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Filament\Superuser\Resources\UserResource\Pages;
use App\Filament\Superuser\Resources\UserResource\RelationManagers\OfficesRelationManager;
use App\Filament\Superuser\Resources\UserResource\RelationManagers\ScannersRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
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
                            ->markAsRequired()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position')
                            ->columnSpan(2)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->columnSpan(2)
                            ->rule('required')
                            ->markAsRequired()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->columnSpan(2)
                            ->rule('required')
                            ->rule('email')
                            ->unique(ignoreRecord: true)
                            ->markAsRequired()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->columnSpan(2)
                            ->password()
                            ->rule(Password::default())
                            ->rule(fn (string $operation) => $operation === 'create' ? 'required' : null)
                            ->markAsRequired(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => ! empty($state))
                            ->requiredWith('passwordConfirmation')
                            ->same('passwordConfirmation')
                            ->hiddenOn(['view', 'edit']),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->columnSpan(2)
                            ->password()
                            ->rule(fn (string $operation) => $operation === 'create' ? 'required' : null)
                            ->markAsRequired(fn (string $operation) => $operation === 'create')
                            ->requiredWith('password')
                            ->dehydrated(false)
                            ->hiddenOn(['view', 'edit']),
                    ]),
                Forms\Components\Section::make('Employee Account Link')
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
                                        ->filter(fn ($value) => Auth::user()->root ? true : ! in_array($value, [UserRole::ROOT->value, UserRole::DEVELOPER->value]))
                                        ->mapWithKeys(fn ($value, $name) => [$value => UserRole::tryFrom($value)->getLabel()])
                                        ->toArray();
                                }),
                        ])->columnSpan(1),
                        Forms\Components\Group::make([
                            Forms\Components\CheckboxList::make('permissions')
                                ->visible(fn (Get $get) => in_array(UserRole::SUPERUSER->value, $get('roles')))
                                ->dehydratedWhenHidden()
                                ->dehydrateStateUsing(fn (Get $get, array $state) => in_array(UserRole::SUPERUSER->value, $get('roles')) ? $state : [])
                                ->hint('Select resources that the superuser can access.')
                                ->bulkToggleable()
                                ->columns(2)
                                ->options(UserPermission::class),
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
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('offices.code')
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
                Tables\Filters\SelectFilter::make('offices')
                    ->relationship('offices', 'code')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->native(false),
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->deferLoading()
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            ScannersRelationManager::class,
            OfficesRelationManager::class,
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
            // ->whereNot('id', auth()->id())
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
