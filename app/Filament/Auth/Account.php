<?php

namespace App\Filament\Auth;

use App\Actions\OptimizeImage;
use App\Models\Social;
use App\Traits\CanSendEmailVerification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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
                                                    ->imageCropAspectRatio('4:3')
                                                    ->imageEditorAspectRatios(['4:3', '1:1', '3:4'])
                                                    ->acceptedFileTypes(['image/png', 'image/webp', 'image/x-webp'])
                                                    ->maxSize(1024)
                                                    ->downloadable()
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                                                    )
                                                    ->helperText('Your signature specimen to be affixed in a signature field when signing a document.')
                                                    ->hintIcon('heroicon-o-question-mark-circle')
                                                    ->hintIconTooltip('The specimen should be a PNG image with a transparent background.'),
                                                FileUpload::make('certificate')
                                                    ->required()
                                                    ->disk('fake')
                                                    ->reactive()
                                                    ->acceptedFileTypes(['application/x-pkcs12'])
                                                    ->downloadable()
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'data:'.$file->getMimeType().';base64,'.base64_encode($file->getContent())
                                                    )
                                                    ->helperText('Your certificate to be used to cryptographically sign a document to prove its authenticity.')
                                                    ->hintIcon('heroicon-o-question-mark-circle')
                                                    ->hintIconTooltip('The certificate should be a valid PKCS#12 file.'),
                                                TextInput::make('password')
                                                    ->password()
                                                    ->visible(fn (Get $get) => current($get('certificate')) instanceof TemporaryUploadedFile)
                                                    ->required(fn (Get $get) => current($get('certificate')) instanceof TemporaryUploadedFile)
                                                    ->dehydratedWhenHidden()
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
                                            ])
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, OptimizeImage $optimizer) {
                                                $image = explode(',', $data['specimen'])[1];

                                                ['mime' => $mime, 'content' => $image] = $optimizer(base64_decode($image));

                                                $data['specimen'] = "data:{$mime};base64,".base64_encode($image);

                                                return $data;
                                            })
                                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, OptimizeImage $optimizer) {
                                                $image = explode(',', $data['specimen'])[1];

                                                ['mime' => $mime, 'content' => $image] = $optimizer(base64_decode($image));

                                                $data['specimen'] = "data:{$mime};base64,".base64_encode($image);

                                                if ($data['password'] ?? null === null) {
                                                    unset($data['password']);
                                                }

                                                return $data;
                                            }),
                                    ]),
                                Tab::make('Socials')
                                    ->schema([
                                        Section::make()
                                            ->schema($this->getOathProviders()),
                                    ]),
                            ]),
                    ]),
            ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...collect(config('services.oath_providers'))->mapWithKeys(function (string $provider) {
                $social = $this->getUser()->socials->first(fn ($social) => $social->provider === $provider);

                return ["socialite-$provider" => $social?->data?->email];
            })->toArray(),
        ];
    }

    protected function getOathProviders(): array
    {
        $socials = $this->getUser()->socials;

        return collect(config('services.oath_providers'))->map(function (string $provider) use ($socials) {
            $email = $this->getUser()->email;

            $social = $socials->first(fn ($social) => $social->provider === $provider);

            $unlink = <<<HTML
                After unlinking your $provider account,
                you will still be able log in with it
                as long as the email address used matches your account email
                <i>({$email})</i>.
            HTML;

            $link = <<<HTML
                Link your $provider account to this account to enable single sign-on.
            HTML;

            return TextInput::make("socialite-$provider")
                ->label(ucfirst($provider))
                ->prefixIcon("fab-$provider")
                ->readOnly()
                ->dehydrated(false)
                ->hintActions([
                    Action::make("link-$provider")
                        ->visible($social === null)
                        ->label('Link')
                        ->icon('gmdi-link-o')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalIcon('gmdi-link-o')
                        ->modalHeading(str('<i></i>')->toHtmlString())
                        ->modalDescription(str($link)->toHtmlString())
                        ->modalSubmitActionLabel('Continue')
                        ->action(function () use ($provider) {
                            $this->linkSocialAccount($provider);
                        }),
                    Action::make("unlink-$provider")
                        ->visible($social !== null)
                        ->label('Unlink')
                        ->icon('gmdi-link-off-o')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalIcon('gmdi-link-off-o')
                        ->modalHeading(str('<i></i>')->toHtmlString())
                        ->modalDescription(str($unlink)->toHtmlString())
                        ->modalSubmitActionLabel('Unlink')
                        ->action(function () use ($social) {
                            $this->unlinkSocialAccount($social);
                        }),
                ]);
        })->toArray();
    }

    protected function linkSocialAccount(string $provider): void {}

    protected function unlinkSocialAccount(Social $social): void
    {
        $social->delete();

        $this->form->fill([
            "socialite-{$social->provider}" => null,
        ]);

        Notification::make()
            ->title('Social account unlinked')
            ->body("Your {$social->provider} account has been unlinked.")
            ->success()
            ->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        parent::handleRecordUpdate($record, $data);

        if ($record->wasChanged('email')) {
            $record->forceFill(['email_verified_at' => null])->save();

            $this->sendEmailVerificationNotification($record);
        }

        return $record;
    }
}
