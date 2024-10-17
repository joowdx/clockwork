<?php

namespace App\Filament\Auth;

use App\Traits\CanSendEmailVerification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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

class Profile extends EditProfile
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
                                        TextInput::make('uid')
                                            ->label('UID')
                                            ->minLength(8)
                                            ->maxLength(8)
                                            ->alphaNum()
                                            ->required()
                                            ->dehydrateStateUsing(fn (string $state) => strtoupper($state))
                                            ->unique('employees', 'uid', ignoreRecord: true),
                                        Select::make('sex')
                                            ->required()
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ]),
                                        DatePicker::make('birthdate')
                                            ->label('Birthdate')
                                            ->required()
                                            ->format('Y-m-d'),
                                        TextInput::make('number')
                                            ->placeholder('9xxxxxxxxx')
                                            ->mask('9999999999')
                                            ->prefix('+63 ')
                                            ->minLength(10)
                                            ->maxLength(10)
                                            ->markAsRequired()
                                            ->rule('required')
                                            ->rule(fn () => function ($a, $v, $f) {
                                                if (! preg_match('/^9.*/', $v)) {
                                                    $f('Incorrect number format');
                                                }
                                            }),
                                        $this->getEmailFormComponent()
                                            ->label('Email')
                                            ->rules(['required', 'email:rfc,strict,dns,spoof,filter'])
                                            ->helperText(function () {
                                                $help = <<<'HTML'
                                                    <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                                                        Please be cautious when changing your email address as unreachable email addresses may result in account lockout.
                                                    </span>
                                                HTML;

                                                return str($help)->toHtmlString();
                                            }),
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
                                                    ->required()
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
