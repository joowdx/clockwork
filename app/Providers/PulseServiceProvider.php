<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\PulseServiceProvider as ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::define('viewPulse', fn ($user) => $user->developer && $user->superuser);
    }
}
