<?php

namespace App\Providers;

use App\Models\Token;
use Filament\Forms\Components\Select;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Assets\Css;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');

        App::bind(LoginResponse::class, \App\Http\Responses\LoginResponse::class);

        App::bind(LogoutResponse::class, \App\Http\Responses\LogoutResponse::class);

        Sanctum::usePersonalAccessTokenModel(Token::class);

        FilamentAsset::register([Css::make('app', __DIR__.'/../../resources/css/app.css'), Css::make('blade', Vite::asset('resources/css/blade.css'))]);

        Select::configureUsing(fn (Select $select) => $select->native(false));

        Table::configureUsing(fn (Table $table) => $table->paginated([10, 25, 50, 100])->defaultPaginationPageOption(25)->striped());

        Notifications::verticalAlignment(VerticalAlignment::End);

        Notifications::alignment(Alignment::Start);

        FilamentIcon::register(['panels::user-menu.logout-button' => 'gmdi-logout-o', 'panels::user-menu.profile-item' => 'gmdi-account-circle-o']);

        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
