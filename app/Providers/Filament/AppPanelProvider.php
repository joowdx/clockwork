<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\Verification;
use App\Http\Middleware\Authenticate;
use App\Http\Responses\LoginResponse;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

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
            ->passwordReset()
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([Redirect::class])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling(15)
            ->userMenuItems(Navigation::menuItems());
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
