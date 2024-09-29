<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Account;
use App\Filament\Superuser\Resources\EmployeeResource;
use App\Filament\Superuser\Resources\GroupResource;
use App\Filament\Superuser\Resources\HolidayResource;
use App\Filament\Superuser\Resources\OfficeResource;
use App\Filament\Superuser\Resources\ScannerResource;
use App\Filament\Superuser\Resources\ScheduleResource;
use App\Http\Middleware\Authenticate;
use App\Providers\Filament\Utils\Middleware;
use App\Providers\Filament\Utils\Navigation;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class ManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Clockwork')
            ->brandLogo(fn () => view('banner'))
            ->id('manager')
            ->profile(Account::class)
            ->path(str(settings('manager') ?: 'manager')->slug())
            ->colors(['primary' => Color::Cyan])
            ->discoverResources(in: app_path('Filament/Manager/Resources'), for: 'App\\Filament\\Manager\\Resources')
            ->discoverPages(in: app_path('Filament/Manager/Pages'), for: 'App\\Filament\\Manager\\Pages')
            ->pages([Pages\Dashboard::class])
            ->resources([
                EmployeeResource::class,
                OfficeResource::class,
                GroupResource::class,
                HolidayResource::class,
                ScannerResource::class,
                ScheduleResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Manager/Widgets'), for: 'App\\Filament\\Manager\\Widgets')
            ->middleware(Middleware::middlewares())
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->userMenuItems(Navigation::menuItems());
        // ->spaUrlExceptions(Navigation::spaExceptions())
        // ->spa()
    }
}
