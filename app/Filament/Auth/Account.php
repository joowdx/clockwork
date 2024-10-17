<?php

namespace App\Filament\Auth;

use App\Traits\CanSendEmailVerification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
                                                    ->required()
                                                    ->disk('fake')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageEditorAspectRatios(['4:3', '1:1', '3:4'])
                                                    ->acceptedFileTypes(['image/png'])
                                                    ->downloadable()
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                                                    ),
                                                FileUpload::make('certificate')
                                                    ->disk('fake')
                                                    ->reactive()
                                                    ->acceptedFileTypes(['application/x-pkcs12'])
                                                    ->downloadable()
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                                                    ),
                                                TextInput::make('password')
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
