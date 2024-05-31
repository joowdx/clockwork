<?php

namespace App\Providers\Filament\Utils;

use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;

class Navigation
{
    public static function menuItems(): array
    {
        $visibility = fn (string $panel = 'app') => request()->user()->canAccessPanel(Filament::getCurrentPanel(), $panel);

        return [
            MenuItem::make('app')
                ->label('Home')
                ->icon('gmdi-home-o')
                ->visible(fn () => $visibility())
                ->url(fn () => route('filament.app.pages.dashboard')),
            MenuItem::make('security')
                ->label(str(settings('security') ?: 'securty')->headline())
                ->icon('gmdi-enhanced-encryption-o')
                ->visible(fn () => $visibility('security'))
                ->url(fn () => route('filament.security.pages.dashboard')),
            MenuItem::make('secretary')
                ->label(str(settings('secretary') ?: 'secretary')->headline())
                ->icon('gmdi-draw-o')
                ->visible(fn () => $visibility('secretary'))
                ->url(fn () => route('filament.secretary.pages.dashboard')),
            MenuItem::make('manager')
                ->label(str(settings('manager') ?: 'manager')->headline())
                ->icon('gmdi-business-center-o')
                ->visible(fn () => $visibility('manager'))
                ->url(fn () => route('filament.manager.pages.dashboard')),
            MenuItem::make('director')
                ->label(str(settings('director') ?: 'director')->headline())
                ->icon('gmdi-double-arrow-o')
                ->visible(fn () => $visibility('director'))
                ->url(fn () => route('filament.director.pages.dashboard')),
            MenuItem::make('bureaucrat')
                ->label(str(settings('bureaucrat') ?: 'bureaucrat')->headline())
                ->icon('gmdi-history-edu-o')
                ->visible(fn () => $visibility('bureaucrat'))
                ->url(fn () => route('filament.bureaucrat.pages.dashboard')),
            MenuItem::make('executive')
                ->label(str(settings('executive') ?: 'bureaucrat')->headline())
                ->icon('gmdi-stars-o')
                ->visible(fn () => $visibility('executive'))
                ->url(fn () => route('filament.executive.pages.dashboard')),
            MenuItem::make('developer')
                ->label(str(settings('developer') ?: 'developer')->headline())
                ->icon('gmdi-terminal-o')
                ->visible(fn () => $visibility('developer'))
                ->url(fn () => route('filament.developer.pages.dashboard')),
            MenuItem::make('superuser')
                ->label(str(settings('superuser') ?: 'superuser')->headline())
                ->icon('gmdi-security-o')
                ->visible(fn () => $visibility('superuser'))
                ->url(fn () => route('filament.superuser.pages.dashboard')),
        ];
    }

    public static function spaExceptions(): \Closure
    {
        return function (): array {
            $routes = [
                route('filament.app.pages.dashboard'),
                route('filament.security.pages.dashboard'),
                route('filament.secretary.pages.dashboard'),
                route('filament.manager.pages.dashboard'),
                route('filament.director.pages.dashboard'),
                route('filament.bureaucrat.pages.dashboard'),
                route('filament.executive.pages.dashboard'),
                route('filament.developer.pages.dashboard'),
                route('filament.superuser.pages.dashboard'),
            ];

            return collect($routes)
                ->reject(fn ($route) => str($route)->contains(Filament::getCurrentPanel()->getId()))
                ->toArray();
        };
    }
}
