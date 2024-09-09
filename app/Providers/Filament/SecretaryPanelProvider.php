<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Profile;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class SecretaryPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('secretary')
            ->profile(Profile::class, false)
            ->path(str(settings('secretary') ?: 'secretary')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Secretary/Resources'), for: 'App\\Filament\\Secretary\\Resources')
            ->discoverPages(in: app_path('Filament/Secretary/Pages'), for: 'App\\Filament\\Secretary\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Secretary/Widgets'), for: 'App\\Filament\\Secretary\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
