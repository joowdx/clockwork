<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Filament\Manager\Resources\TimesheetResource;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class DirectorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('director')
            ->profile(Account::class)
            ->path(str(settings('director') ?: 'director')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Director/Resources'), for: 'App\\Filament\\Director\\Resources')
            ->discoverPages(in: app_path('Filament/Director/Pages'), for: 'App\\Filament\\Director\\Pages')
            ->resources([TimesheetResource::class])
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Director/Widgets'), for: 'App\\Filament\\Director\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
