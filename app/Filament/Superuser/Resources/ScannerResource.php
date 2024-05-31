<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\ScannerResource\Pages;
use App\Filament\Superuser\Resources\ScannerResource\RelationManagers\EmployeesRelationManager;
use App\Filament\Superuser\Resources\ScannerResource\RelationManagers\UsersRelationManager;
use App\Jobs\FetchTimelogs;
use App\Models\Scanner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class ScannerResource extends Resource
{
    protected static ?string $model = Scanner::class;

    protected static ?string $navigationIcon = 'gmdi-touch-app-o';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(): array
    {
        $isCalledBySelf = @debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['class'] === get_called_class();

        return [
            Forms\Components\Section::make('Scanner Details')
                ->columns()
                ->schema([
                    Forms\Components\ToggleButtons::make('priority')
                        ->required()
                        ->boolean()
                        ->grouped()
                        ->inline()
                        ->default(false)
                        ->columnSpanFull()
                        ->helperText('Prioritized scanners have higher precedence over others.'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->alphaDash()
                        ->dehydrateStateUsing(fn (string $state): ?string => mb_strtolower($state)),
                    Forms\Components\TextInput::make('uid')
                        ->hint('Device ID')
                        ->label('UID')
                        ->numeric()
                        ->type('text')
                        // ->rules(['required', 'min:2', 'max:255'])
                        ->markAsRequired()
                        ->dehydrateStateUsing(fn (?string $state): ?int => (int) $state),
                    Forms\Components\Textarea::make('remarks')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Printout Configuration')
                ->columns()
                ->schema([
                    Forms\Components\ColorPicker::make('print.foreground_color')
                        ->rgba()
                        ->label('Foreground Color')
                        ->default('rgba(0, 0, 0, 1)')
                        ->helperText('The color of the text in the printout.'),
                    Forms\Components\ColorPicker::make('print.background_color')
                        ->rgba()
                        ->label('Background Color')
                        ->default('rgba(0, 0, 0, 0)')
                        ->helperText('The color of the background in the printout.'),
                    // Forms\Components\TextInput::make('print.font_size')
                    //     ->label('Font Size')
                    //     ->numeric()
                    //     ->default(12)
                    //     ->type('text')
                    //     ->rules(['min:1', 'max:100'])
                    //     ->helperText('The size of the font in the printout.'),
                    // Forms\Components\Select::make('print.font_style')
                    //     ->label('Font Style')
                    //     ->native(false)
                    //     ->options([
                    //         'font-normal not-italic' => 'Normal',
                    //         'font-bold not-italic' => 'Bold',
                    //         'font-normal italic' => 'Italic',
                    //         'font-bold italic' => 'Bold Italic',
                    //     ])
                    //     ->default('font-normal not-italic')
                    //     ->helperText('The style of the font in the printout.'),
                ]),
            Forms\Components\Section::make('Connection Parameters')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('host')
                        ->requiredWith('port')
                        ->helperText('The hostname or IP address of the scanner.'),
                    Forms\Components\TextInput::make('port')
                        ->numeric()
                        ->type('text')
                        ->helperText('The port number of the scanner.'),
                    Forms\Components\TextInput::make('pass')
                        ->password()
                        ->helperText('The password of the scanner.'),
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
                Tables\Columns\TextColumn::make('uid')
                    ->extraCellAttributes(['class' => 'font-mono'])
                    ->placeholder('<blank>')
                    ->label('UID')
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw("CAST(uid as UNSIGNED) $direction"))
                    ->searchable(query: fn ($query, $search) => $query->where('uid', $search)),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timelogs_count')
                    ->label('Timelogs')
                    ->counts('timelogs')
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
                // Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('fetch')
                    ->icon('heroicon-m-arrow-path-rounded-square')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-m-arrow-path-rounded-square')
                    ->modalHeading('Fetch timelogs')
                    ->closeModalByClickingAway(false)
                    ->modalDescription(function (Scanner $record) {
                        if (empty($record->host)) {
                            return 'Device connection is not yet configured. Please set it up first before using this feature.';
                        }

                        return 'Would you like to fetch timelogs directly from the scanner?';
                    })
                    ->modalCancelActionLabel(function (Scanner $record) {
                        if (empty($record->host)) {
                            return 'Close';
                        }
                    })
                    ->modalSubmitAction(function (Scanner $record) {
                        if (empty($record->host)) {
                            return false;
                        }
                    })
                    ->form([
                        Forms\Components\Toggle::make('filter')
                            ->hidden()
                            ->label('Process all')
                            ->live()
                            ->dehydrated(false)
                            ->default(false)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, bool $state, $livewire) {
                                if ($state) {
                                    $set('month', $get('month') ?? today()->format('Y-m'));

                                    $livewire->validateOnly('month');
                                }
                            }),
                        Forms\Components\TextInput::make('month')
                            ->disabled(fn (Forms\Get $get) => $get('filter'))
                            ->hidden(fn (Scanner $record) => empty($record->host))
                            ->helperText(function () {
                                return 'Only process the timelogs of the specified month. However, this will still attempt to fetch all timelogs directly from the terminal due to the limitation of the device.';
                            })
                            ->default(today()->format('Y-m'))
                            ->live()
                            ->markAsRequired(true)
                            ->type('month')
                            ->rules(['required']),
                    ])
                    ->action(function (Scanner $record, array $data) {
                        if (empty($record->uid) || empty($record->host)) {
                            Notification::make()
                                ->danger()
                                ->title('Fetch failed')
                                ->body("You need to set the device's uid and its hostname or ip address first.")
                                ->send();

                            return;
                        }

                        FetchTimelogs::dispatch($record->uid, $data['month']);

                        Notification::make()
                            ->success()
                            ->title('Command queued')
                            ->body(str("We'll notify you once the timelogs of {$record->name} have been fetched.")->toHtmlString())
                            ->send();
                    }),
                // ]),
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
            'index' => Pages\ListScanners::route('/'),
            'create' => Pages\CreateScanner::route('/create'),
            'edit' => Pages\EditScanner::route('/{record}/edit'),
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
