<?php

namespace App\Filament\Auth;

use App\Filament\Superuser\Resources\SignatureResource;
use App\Models\Signature;
use App\Traits\CanSendEmailVerification;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LSNepomuceno\LaravelA1PdfSign\Exceptions\ProcessRunTimeException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use SensitiveParameter;

class Account extends EditProfile
{
    use CanSendEmailVerification;

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->schema([
                        Tabs::make()
                            ->contained(false)
                            ->schema([
                                Tab::make('Information')
                                    ->schema([
                                        TextInput::make('username')
                                            ->dehydrated(false)
                                            ->disabled()
                                            ->markAsRequired(),
                                        $this->getNameFormComponent(),
                                        $this->getEmailFormComponent()
                                            ->rules(['required', 'email:strict,rfc,dns,spoof,filter']),
                                        TextInput::make('position')
                                            ->maxLength(255),
                                    ]),
                                Tab::make('Password')
                                    ->schema([
                                        TextInput::make('current_password')
                                            ->dehydrated(false)
                                            ->password()
                                            ->currentPassword()
                                            ->requiredWith('password'),
                                        $this->getPasswordFormComponent(),
                                        $this->getPasswordConfirmationFormComponent(),
                                    ]),
                                Tab::make('Signature')
                                    ->schema([
                                        Repeater::make('signature')
                                            ->relationship('signature')
                                            ->inlineLabel(false)
                                            ->maxItems(1)
                                            ->addActionLabel('Configure signature')
                                            ->hiddenLabel()
                                            ->schema([
                                                FileUpload::make('specimen')
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
                                                        Action::make('upload')
                                                            ->requiresConfirmation()
                                                            ->modalIcon('heroicon-o-arrow-up-tray')
                                                            ->modalDescription('Please ensure that the specimen has a clear and transparent background.')
                                                            ->form([
                                                                FileUpload::make('tmp')
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
                                                            ->action(function (Set $set, array $data) {
                                                                $set('specimen', [$data['tmp']]);
                                                            }),
                                                    ])
                                                    ->hintActions([
                                                        Action::make('preview')
                                                            ->visible(fn (?Signature $record) => $record?->exists)
                                                            ->requiresConfirmation()
                                                            ->modalIcon(null)
                                                            ->modalDescription(null)
                                                            ->modalContent(fn (Signature $record) => SignatureResource::signatureView($record))
                                                            ->modalCancelAction(false)
                                                            ->modalSubmitAction(false)
                                                            ->form(fn (Signature $signature) => [
                                                                Actions::make([
                                                                    Action::make('Download')
                                                                        ->action(fn () => Storage::download($signature->specimen, $signature->signaturable->name)),
                                                                ])->alignRight(),
                                                            ]),
                                                    ]),
                                                FileUpload::make('certificate')
                                                    ->extraInputAttributes(['class' => 'hide-file-upload-label'])
                                                    ->disk('local')
                                                    ->visibility('private')
                                                    ->directory('signatures/certificates')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->previewable(false)
                                                    ->deletable(false)
                                                    ->hintActions([
                                                        Action::make('upload')
                                                            ->requiresConfirmation()
                                                            ->modalIcon('heroicon-o-arrow-up-tray')
                                                            ->modalDescription('Upload a valid certificate file. You will be asked to enter your certificate\'s password every time you sign a document.')
                                                            ->form([
                                                                FileUpload::make('tmp')
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
                                                                TextInput::make('password')
                                                                    ->label('Password')
                                                                    ->markAsRequired()
                                                                    ->rule('required')
                                                                    ->password()
                                                                    ->rule(fn (Get $get) => function ($attribute, #[SensitiveParameter] $value, $fail) use ($get) {
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
                                                            ->action(function (Set $set, array $data) {
                                                                $set('certificate', [$data['tmp']]);

                                                                $set('password', $data['password']);
                                                            }),
                                                        Action::make('Download')
                                                            ->hidden(fn (string $operation, mixed $state) => $operation === 'create' || empty($state))
                                                            ->action(fn (Signature $signature) => Storage::download($signature->certificate, $signature->signaturable->name)),
                                                    ]),
                                                Hidden::make('password')
                                                    ->dehydrated(fn (?string $state) => ! is_null($state)),
                                            ])
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                                                if (str($data['specimen'])->startsWith('livewire-tmp/')) {
                                                    if (file_exists(storage_path('app/'.$data['specimen']))) {
                                                        $file = 'signatures/specimens/'.str($data['specimen'])->afterLast('/');

                                                        if (! is_dir(storage_path('app/signatures/specimens'))) {
                                                            mkdir(storage_path('app/signatures/specimens'), recursive: true);
                                                        }

                                                        rename(storage_path('app/'.$data['specimen']), storage_path('app/'.$file));

                                                        $data['specimen'] = $file;
                                                    } else {
                                                        $data['specimen'] = null;
                                                    }
                                                }

                                                if (str($data['certificate'])->startsWith('livewire-tmp/')) {
                                                    if (file_exists(storage_path('app/'.$data['certificate']))) {
                                                        $file = 'signatures/certificates/'.str($data['certificate'])->afterLast('/');

                                                        if (! is_dir(storage_path('app/signatures/certificates'))) {
                                                            mkdir(storage_path('app/signatures/certificates'), recursive: true);
                                                        }

                                                        rename(storage_path('app/'.$data['certificate']), storage_path('app/'.$file));

                                                        $data['certificate'] = $file;
                                                    } else {
                                                        $data['certificate'] = null;
                                                    }
                                                }

                                                return $data;
                                            })
                                            ->mutateRelationshipDataBeforeSaveUsing(function (Signature $record, array $data) {
                                                if (str($data['specimen'])->startsWith('livewire-tmp/')) {
                                                    if (file_exists(storage_path('app/'.$data['specimen']))) {
                                                        $file = 'signatures/specimens/'.str($data['specimen'])->afterLast('/');

                                                        if (! is_dir(storage_path('app/signatures/specimens'))) {
                                                            mkdir(storage_path('app/signatures/specimens'), recursive: true);
                                                        }

                                                        if (file_exists(storage_path('app/'.$record->specimen))) {
                                                            unlink(storage_path('app/'.$record->specimen));
                                                        }

                                                        rename(storage_path('app/'.$data['specimen']), storage_path('app/'.$file));

                                                        $data['specimen'] = $file;
                                                    }
                                                }

                                                if (str($data['certificate'])->startsWith('livewire-tmp/')) {
                                                    if (file_exists(storage_path('app/'.$data['certificate']))) {
                                                        $file = 'signatures/certificates/'.str($data['certificate'])->afterLast('/');

                                                        if (! is_dir(storage_path('app/signatures/certificates'))) {
                                                            mkdir(storage_path('app/signatures/certificates'), recursive: true);
                                                        }

                                                        if ($record->certificate && file_exists(storage_path('app/'.$record->certificate))) {
                                                            unlink(storage_path('app/'.$record->certificate));
                                                        }

                                                        rename(storage_path('app/'.$data['certificate']), storage_path('app/'.$file));

                                                        $data['certificate'] = $file;
                                                    }
                                                }

                                                return $data;
                                            }),
                                    ]),
                            ]),
                    ]),
            ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        if ($record->wasChanged('email')) {
            $record->forceFill(['email_verified_at' => null])->save();

            $this->sendEmailVerificationNotification($record);
        }

        return $record;
    }
}
