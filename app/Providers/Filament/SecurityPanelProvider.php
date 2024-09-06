<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class SecurityPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('security')
            ->path(str(settings('security') ?: 'security')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Security/Resources'), for: 'App\\Filament\\Security\\Resources')
            ->discoverPages(in: app_path('Filament/Security/Pages'), for: 'App\\Filament\\Security\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Security/Widgets'), for: 'App\\Filament\\Security\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
