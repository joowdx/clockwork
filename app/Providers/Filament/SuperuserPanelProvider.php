<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Profile;
use App\Filament\Superuser\Pages\Dashboard;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class SuperuserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('superuser')
            ->profile(Profile::class, false)
            ->path(str(settings('superuser') ?: 'superuser')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Superuser/Resources'), for: 'App\\Filament\\Superuser\\Resources')
            ->discoverPages(in: app_path('Filament/Superuser/Pages'), for: 'App\\Filament\\Superuser\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Superuser/Widgets'), for: 'App\\Filament\\Superuser\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
