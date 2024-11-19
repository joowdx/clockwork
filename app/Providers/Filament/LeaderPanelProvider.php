<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Filament\Auth\Verification;
use App\Filament\Manager\Resources\TimesheetResource;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class LeaderPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('leader')
            ->profile(Account::class)
            ->emailVerification(Verification::class)
            ->path(str(settings('leader') ?: 'leader')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Leader/Resources'), for: 'App\\Filament\\Leader\\Resources')
            ->discoverPages(in: app_path('Filament/Leader/Pages'), for: 'App\\Filament\\Leader\\Pages')
            ->resources([TimesheetResource::class])
            ->discoverWidgets(in: app_path('Filament/Leader/Widgets'), for: 'App\\Filament\\Leader\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling('45s')
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
