<?php

namespace App\Filament\Superuser\Resources;

use App\Filament\Superuser\Resources\SignatureResource\Pages;
use App\Models\Employee;
use App\Models\Signature;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
            ->columns(3)
            ->schema([
                $signaturable::make('signaturable')
                    ->label('Owner')
                    ->native(false)
                    ->columnSpan(1)
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
                    ->columns(1)
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\FileUpload::make('specimen')
                            ->required()
                            ->disk('fake')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['4:3', '1:1', '3:4'])
                            ->acceptedFileTypes(['image/png', 'image/webp', 'image/x-webp'])
                            ->downloadable()
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                            ),
                        Forms\Components\FileUpload::make('certificate')
                            ->required()
                            ->disk('fake')
                            ->reactive()
                            ->acceptedFileTypes(['application/x-pkcs12'])
                            ->downloadable()
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                            ),
                        Forms\Components\TextInput::make('password')
                            ->visible(fn (Get $get) => current($get('certificate')) instanceof TemporaryUploadedFile)
                            ->password()
                            ->requiredWith('certificate')
                            ->rule(fn (Get $get) => function ($attribute, #[SensitiveParameter] $value, $fail) use ($get) {
                                if (empty($value) || empty($get('certificate'))) {
                                    return;
                                }

                                if (! current($get('certificate')) instanceof TemporaryUploadedFile) {
                                    return;
                                }

                                try {
                                    (new ManageCert)->setPreservePfx()->fromUpload(current($get('certificate')), $value);
                                } catch (ProcessRunTimeException $exception) {
                                    if (str($exception->getMessage())->contains('password')) {
                                        $fail('The password is incorrect.');
                                    }
                                }
                            }),
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
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
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
                // SoftDeletingScope::class,
            ]);
    }

    public static function signatureView(Signature $signature): Htmlable
    {
        $html = <<<HTML
            <div style="display:flex;justify-content:center;background:white;border-radius:0.5em;padding:1em;">
                <img src="data:image/png;base64,{$signature->specimenBase64}" style="height:100%;width:auto;">
            </div>
        HTML;

        return str($html)->toHtmlString();
    }
}
