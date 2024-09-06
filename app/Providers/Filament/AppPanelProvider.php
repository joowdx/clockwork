<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Http\Responses\LoginResponse;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Http\Middleware\Authenticate;
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
            ->registration()
            ->revealablePasswords(false)
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([Redirect::class])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}

class Redirect extends Pages\Dashboard
{
    public function __construct()
    {
        (new LoginResponse)->toResponse(request());
    }
}
