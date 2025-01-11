<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\Recover;
use App\Filament\Auth\Reset;
use App\Filament\Auth\Verification;
use App\Http\Middleware\Authenticate;
use App\Http\Responses\LoginResponse;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Exception;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class AuthPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('auth')
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->path('auth')
            ->login(Login::class)
            ->revealablePasswords(false)
            ->emailVerification(Verification::class)
            ->passwordReset(Reset::class, Recover::class)
            ->colors(['primary' => Color::Cyan])
            ->pages([Redirect::class])
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling(fn () => '300s')
            ->userMenuItems(Navigation::menuItems());
    }
}

class Redirect extends Pages\Dashboard
{
    public function __construct()
    {
        (new LoginResponse)->toResponse(request());
    }

    public function mount(): void
    {
        (new LoginResponse)->toResponse(request());
    }
}
