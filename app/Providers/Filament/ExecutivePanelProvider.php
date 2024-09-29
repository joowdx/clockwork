<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class ExecutivePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('executive')
            ->profile(Account::class)
            ->path(str(settings('executive') ?: 'executive')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Executive/Resources'), for: 'App\\Filament\\Executive\\Resources')
            ->discoverPages(in: app_path('Filament/Executive/Pages'), for: 'App\\Filament\\Executive\\Pages')
            ->discoverWidgets(in: app_path('Filament/Executive/Widgets'), for: 'App\\Filament\\Executive\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
