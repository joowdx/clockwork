<?php

namespace App\Http\Controllers;

use DutchCodingCompany\FilamentSocialite\Events\RegistrationNotEnabled;
use DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected;
use DutchCodingCompany\FilamentSocialite\Events\UserNotAllowed;
use DutchCodingCompany\FilamentSocialite\Exceptions\ProviderNotConfigured;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Http\Controllers\SocialiteLoginController;
use DutchCodingCompany\FilamentSocialite\Http\Middleware\PanelFromUrlQuery;
use DutchCodingCompany\FilamentSocialite\Models\Contracts\FilamentSocialiteUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialiteController extends SocialiteLoginController
{
    private ?FilamentSocialitePlugin $plugin = null;

    public function redirectToProvider(string $provider): mixed
    {
        if (! $this->plugin()->isProviderConfigured($provider)) {
            return $this->redirectToLogin('You are not authorized to access this page.');
        }

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        $response = $driver
            ->with([
                ...$this->plugin()->getProvider($provider)->getWith(),
                'state' => $state = PanelFromUrlQuery::encrypt($this->plugin()->getPanel()->getId()),
            ])
            ->scopes($this->plugin()->getProvider($provider)->getScopes())
            ->redirect();

        session()->put('state', $state);

        session()->put('guard', request()->input('guard'));

        if (request()->filled('link')) {
            session()->put('link', (bool) request()->input('link'));
        }

        return $response;
    }

    public function processCallback(string $provider): Response
    {
        if (! $this->plugin()->isProviderConfigured($provider)) {
            throw ProviderNotConfigured::make($provider);
        }

        $oauthUser = $this->retrieveOauthUser($provider);

        if (is_null($oauthUser)) {
            return $this->redirectToLogin('filament-socialite::auth.login-failed');
        }

        if (! $this->authorizeUser($oauthUser)) {
            UserNotAllowed::dispatch($oauthUser);

            return $this->redirectToLogin('filament-socialite::auth.user-not-allowed');
        }

        $socialiteUser = $this->retrieveSocialiteUser($provider, $oauthUser);

        if ($socialiteUser) {
            return $this->loginUser($provider, $socialiteUser, $oauthUser);
        }

        $user = app()->call($this->plugin()->getResolveUserUsing(), [
            'provider' => $provider,
            'oauthUser' => $oauthUser,
            'plugin' => $this->plugin,
        ]);

        if (! $this->evaluate($this->plugin()->getRegistration(), ['provider' => $provider, 'oauthUser' => $oauthUser, 'user' => $user])) {
            RegistrationNotEnabled::dispatch($provider, $oauthUser, $user);

            return $this->redirectToLogin('filament-socialite::auth.registration-not-enabled');
        }

        return $user
            ? $this->registerSocialiteUser($provider, $oauthUser, $user)
            : $this->redirectToLogin('User not found.');
    }

    protected function registerSocialiteUser(string $provider, User $oauthUser, Authenticatable $user): Response
    {
        $socialiteUser = $this->plugin()->getSocialiteUserModel()::createForProvider($provider, $oauthUser, $user, $this->getModel());

        SocialiteUserConnected::dispatch($socialiteUser);

        return $this->loginUser($provider, $socialiteUser, $oauthUser);
    }

    protected function retrieveSocialiteUser(string $provider, User $oauthUser): ?FilamentSocialiteUser
    {
        return $this->plugin()->getSocialiteUserModel()::findForProvider($provider, $oauthUser, $this->getModel());
    }

    protected function plugin(): FilamentSocialitePlugin
    {
        parent::plugin();

        return $this->plugin ??= FilamentSocialitePlugin::current();
    }

    protected function getModel(): string
    {
        return match (session()->get('guard')) {
            'employee' => \App\Models\Employee::class,
            default => \App\Models\User::class,
        };
    }
}
