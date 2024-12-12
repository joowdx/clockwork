<?php

namespace App\Providers;

use App\Resolver\PulseUserResolver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\PulseServiceProvider as ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::define('viewPulse', fn ($user) => true);

        App::singleton(ResolvesUsers::class, PulseUserResolver::class);
    }
}
