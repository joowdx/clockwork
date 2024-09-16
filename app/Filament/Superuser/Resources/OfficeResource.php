<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\OfficeResource\Pages;
use App\Filament\Superuser\Resources\OfficeResource\RelationManagers\EmployeesRelationManager;
use App\Filament\Superuser\Resources\OfficeResource\RelationManagers\UsersRelationManager;
use App\Models\Office;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'gmdi-corporate-fare-o';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(bool $head = false): array
    {
        $isCalledBySelf = @debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['class'] === get_called_class();

        return [
            Forms\Components\Section::make('General information')
                ->columns(5)
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->helperText('The office\'s official logo.')
                        ->visibility('public')
                        ->getUploadedFileNameForStorageUsing(fn ($file, $get) => 'offices/'.mb_strtolower($get('code')).'.'.$file->extension())
                        ->imageEditor()
                        ->avatar()
                        ->maxSize(2048),
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->helperText('The full expanded name of the office.'),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The shorthand name of the office.'),
                    ])->columnSpan(4),
                ]),
            Forms\Components\Section::make('Office head')
                ->hiddenOn(['create'])
                ->schema([
                    Forms\Components\Select::make('head')
                        ->relationship('head', 'full_name', fn ($query, $record) => $query->whereHas('offices', fn ($q) => $q->where('offices.id', $record?->id)))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->nullable()
                        ->hiddenLabel()
                        ->editOptionForm($isCalledBySelf ? EmployeeResource::formSchema() : null)
                        ->createOptionForm($isCalledBySelf ? EmployeeResource::formSchema() : null)
                        ->visible(fn () => $isCalledBySelf || $head),
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->toggleable(),
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
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            EmployeesRelationManager::class,
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
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
