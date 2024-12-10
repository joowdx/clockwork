<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Filament\Auth\Verification;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class BureaucratPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('bureaucrat')
            ->profile(Account::class)
            ->emailVerification(Verification::class)
            ->path(str(settings('bureaucrat') ?: 'bureaucrat')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Bureaucrat/Resources'), for: 'App\\Filament\\Bureaucrat\\Resources')
            ->discoverPages(in: app_path('Filament/Bureaucrat/Pages'), for: 'App\\Filament\\Bureaucrat\\Pages')
            ->discoverWidgets(in: app_path('Filament/Bureaucrat/Widgets'), for: 'App\\Filament\\Bureaucrat\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling(fn () => '300s')
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
