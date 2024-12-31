<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\Recover;
use App\Filament\Auth\Reset;
use App\Filament\Auth\Verification;
use App\Http\Middleware\Authenticate;
use App\Http\Responses\LoginResponse;
use App\Models\Employee;
use App\Models\Social;
use App\Models\User;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\Auth\Authenticatable;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->path('')
            ->default()
            ->login(Login::class)
            ->revealablePasswords(false)
            ->emailVerification(Verification::class)
            ->passwordReset(Reset::class, Recover::class)
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([Redirect::class])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling(fn () => '300s')
            ->userMenuItems(Navigation::menuItems())
            ->plugin(
                FilamentSocialitePlugin::make()
                    ->slug('')
                    ->socialiteUserModelClass(Social::class)
                    ->registration(fn (?Authenticatable $user) => (bool) $user)
                    ->resolveUserUsing(function ($oauthUser) {
                        $model = match (session()->get('guard')) {
                            'employee' => Employee::class,
                            default => User::class,
                        };

                        return $model::where('email', $oauthUser->getEmail())->first();
                    })
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->outlined(false)
                            ->visible(false),
                        Provider::make('microsoft')
                            ->label('Microsoft')
                            ->icon('fab-microsoft')
                            ->outlined(false)
                            ->visible(false),
                    ])
            );
    }
}

class Redirect extends Pages\Dashboard
{
    public function __construct()
    {
        (new LoginResponse)->toResponse(request());
    }

    public function mount()
    {
        (new LoginResponse)->toResponse(request());
    }
}
