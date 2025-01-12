<?php

namespace App\Filament\Auth;

use App\Traits\CanSendEmailVerification;
use Filament\Facades\Filament;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Database\Eloquent\Model;

class Account extends EditProfile
{
    use CanSendEmailVerification;
    use Concerns\SocialOauthProviderLinker;

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
                            ->persistTabInQueryString()
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
                                $this->socialFormTab(),
                            ]),
                    ]),
            ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...collect(config('services.oauth_providers'))->mapWithKeys(function (string $provider) {
                $social = $this->getUser()->socials->first(fn ($social) => $social->provider === $provider);

                return ["socialite-$provider" => $social?->data?->email];
            })->toArray(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        if ($this->getUser()->wasChanged('email')) {
            return route('filament.'.Filament::getCurrentPanel()->getId().'.auth.email-verification.prompt');
        }

        return null;
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
