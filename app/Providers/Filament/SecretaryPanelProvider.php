<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Filament\Auth\Verification;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages\Dashboard;
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
            ->profile(Account::class)
            ->emailVerification(Verification::class)
            ->path(str(settings('secretary') ?: 'secretary')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Secretary/Resources'), for: 'App\\Filament\\Secretary\\Resources')
            ->discoverPages(in: app_path('Filament/Secretary/Pages'), for: 'App\\Filament\\Secretary\\Pages')
            ->discoverWidgets(in: app_path('Filament/Secretary/Widgets'), for: 'App\\Filament\\Secretary\\Widgets')
            ->pages([Dashboard::class])
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling('45s')
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
