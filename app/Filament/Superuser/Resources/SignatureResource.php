<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\SignatureResource\Pages;
use App\Models\Employee;
use App\Models\Signature;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use LSNepomuceno\LaravelA1PdfSign\Exceptions\ProcessRunTimeException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use SensitiveParameter;

class SignatureResource extends Resource
{
    protected static ?string $model = Signature::class;

    protected static ?string $navigationIcon = 'gmdi-rate-review-o';

    public static function form(Form $form): Form
    {
        $signaturable = new class('signature') extends Forms\Components\MorphToSelect
        {
            public function getChildComponents(): array
            {
                $relationship = $this->getRelationship();
                $typeColumn = $relationship->getMorphType();
                $keyColumn = $relationship->getForeignKeyName();

                $types = $this->getTypes();
                $isRequired = $this->isRequired();

                /** @var ?Type $selectedType */
                $selectedType = $types[$this->evaluate(fn (Forms\Get $get): ?string => $get($typeColumn))] ?? null;

                return [
                    Forms\Components\Select::make($typeColumn)
                        ->label('Type')
                        ->options(array_map(fn (Forms\Components\MorphToSelect\Type $type): string => $type->getLabel(), $types))
                        ->native($this->isNative())
                        ->required($isRequired)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set) use ($keyColumn) {
                            $set($keyColumn, null);
                            $this->callAfterStateUpdated();
                        }),
                    Forms\Components\Select::make($keyColumn)
                        ->label($selectedType?->getLabel())
                        ->options($selectedType?->getOptionsUsing)
                        ->getSearchResultsUsing($selectedType?->getSearchResultsUsing)
                        ->getOptionLabelUsing($selectedType?->getOptionLabelUsing)
                        ->native($this->isNative())
                        ->required(filled($selectedType))
                        ->hidden(blank($selectedType))
                        ->dehydratedWhenHidden()
                        ->searchable($this->isSearchable())
                        ->searchDebounce($this->getSearchDebounce())
                        ->searchPrompt($this->getSearchPrompt())
                        ->searchingMessage($this->getSearchingMessage())
                        ->noSearchResultsMessage($this->getNoSearchResultsMessage())
                        ->loadingMessage($this->getLoadingMessage())
                        ->allowHtml($this->isHtmlAllowed())
                        ->optionsLimit($this->getOptionsLimit())
                        ->preload($this->isPreloaded())
                        ->when($this->isLive(), fn (Forms\Components\Select $component) => $component->live(onBlur: $this->isLiveOnBlur()))
                        ->afterStateUpdated(fn () => $this->callAfterStateUpdated()),
                ];
            }
        };

        return $form
            ->schema([
                $signaturable::make('signaturable')
                    ->label('Owner')
                    ->native(false)
                    ->columns(2)
                    ->columnSpanFull()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->types([
                        MorphToSelect\Type::make(User::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(Employee::class)
                            ->titleAttribute('name'),
                    ]),
                Forms\Components\Fieldset::make('Signature')
                    ->schema([
                        Forms\Components\FileUpload::make('specimen')
                            ->extraInputAttributes(['class' => 'hide-file-upload-label'])
                            ->disk('local')
                            ->visibility('private')
                            ->directory('signatures/specimens')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->acceptedFileTypes(['image/png', 'image/webp'])
                            ->previewable(false)
                            ->deletable(false)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('upload')
                                    ->requiresConfirmation()
                                    ->modalIcon('heroicon-o-arrow-up-tray')
                                    ->modalDescription('Please ensure that the specimen has a clear and transparent background.')
                                    ->form([
                                        Forms\Components\FileUpload::make('tmp')
                                            ->disk('local')
                                            ->visibility('private')
                                            ->directory('livewire-tmp')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['4:3', '1:1', '3:4'])
                                            ->getUploadedFileNameForStorageUsing(fn ($state) => current($state)->hashName())
                                            ->label('Specimen')
                                            ->required(),
                                    ])
                                    ->action(function (Forms\Set $set, array $data) {
                                        $set('specimen', [$data['tmp']]);
                                    }),
                            ])
                            ->hintActions([
                                Forms\Components\Actions\Action::make('preview')
                                    ->hidden(fn (string $operation) => $operation === 'create')
                                    ->requiresConfirmation()
                                    ->modalIcon(null)
                                    ->modalDescription(null)
                                    ->modalContent(fn (Signature $record) => static::signatureView($record))
                                    ->modalCancelAction(false)
                                    ->modalSubmitAction(false)
                                    ->form(fn (Signature $signature) => [
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('Download')
                                                ->action(fn () => Storage::download($signature->specimen, $signature->signaturable->name)),
                                        ])->alignRight(),
                                    ]),
                            ]),
                        Forms\Components\FileUpload::make('certificate')
                            ->extraInputAttributes(['class' => 'hide-file-upload-label'])
                            ->disk('local')
                            ->visibility('private')
                            ->directory('signatures/certificates')
                            ->disabled()
                            ->dehydrated()
                            ->previewable(false)
                            ->deletable(false)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('upload')
                                    ->requiresConfirmation()
                                    ->modalIcon('heroicon-o-arrow-up-tray')
                                    ->modalDescription('Upload a valid certificate file. You will be asked to enter your certificate\'s password every time you sign a document.')
                                    ->form([
                                        Forms\Components\FileUpload::make('tmp')
                                            ->disk('local')
                                            ->visibility('private')
                                            ->directory('livewire-tmp')
                                            ->previewable(false)
                                            ->getUploadedFileNameForStorageUsing(fn ($state) => pathinfo(current($state)->hashName(), PATHINFO_FILENAME).'.pfx')
                                            ->label('Certificate')
                                            ->required()
                                            ->rule(fn () => function ($attribute, $value, $fail) {
                                                try {
                                                    (new ManageCert)->setPreservePfx()->fromUpload($value, '');
                                                } catch (ProcessRunTimeException $exception) {
                                                    if (str($exception->getMessage())->contains('password')) {
                                                        return;
                                                    }

                                                    $fail('The file is not a valid certificate.');
                                                }
                                            }),
                                        Forms\Components\TextInput::make('password')
                                            ->label('Password')
                                            ->hint('Optional')
                                            ->password()
                                            ->rule(fn (Forms\Get $get) => function ($attribute, #[SensitiveParameter] $value, $fail) use ($get) {
                                                if (empty($value) || empty($get('tmp'))) {
                                                    return;
                                                }

                                                try {
                                                    (new ManageCert)->setPreservePfx()->fromUpload(current($get('tmp')), $value);
                                                } catch (ProcessRunTimeException $exception) {
                                                    if (str($exception->getMessage())->contains('password')) {
                                                        $fail('The password is incorrect.');
                                                    }
                                                }
                                            }),
                                    ])
                                    ->action(function (Forms\Set $set, array $data) {
                                        $set('certificate', [$data['tmp']]);

                                        $set('password', $data['password']);
                                    }),
                                Forms\Components\Actions\Action::make('password')
                                    ->hidden(fn (Signature $signature, mixed $state) => empty($state) || isset($signature->password))
                                    ->requiresConfirmation()
                                    ->modalIcon('heroicon-o-arrow-up-tray')
                                    ->modalDescription('You can optionally provide your password here.'),
                                Forms\Components\Actions\Action::make('Download')
                                    ->hidden(fn (string $operation, mixed $state) => $operation === 'create' || empty($state))
                                    ->action(fn (Signature $signature) => Storage::download($signature->certificate, $signature->signaturable->name)),
                            ]),
                        Forms\Components\Hidden::make('password'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('signaturable_type')
                    ->label('Type')
                    ->getStateUsing(fn (Signature $record) => class_basename($record->signaturable_type))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signaturable.name')
                    ->label('Owner'),
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
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListSignatures::route('/'),
            'create' => Pages\CreateSignature::route('/create'),
            'edit' => Pages\EditSignature::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function signatureView(Signature $signature): Htmlable
    {
        $html = <<<HTML
            <div style="display:flex;justify-content:center;background:white;border-radius:0.5em;padding:1em;">
                <img src="data:image/png;base64,{$signature->specimenBase64}" style="height:100%;width:auto;">
            </div>
        HTML;

        return str($html)->toHtmlString();
    }
}
