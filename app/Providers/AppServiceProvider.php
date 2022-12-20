<?php

namespace App\Providers;

use App\Contracts\ScannerDriver;
use App\Drivers\TadPhp;
use App\Drivers\ZakZk;
use App\Models\Scanner;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(ImportServiceProvider::class);

        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(ScannerDriver::class, function ($app) {
            $scanner = $app->request->route('scanner') ?? $app->request->scanner ?? $app->request->scanner_id;

            if (! $scanner instanceof Scanner) {
                $scanner = Scanner::find($scanner);
            }

            return match (strtolower($scanner->driver)) {
                'zakzk' => new ZakZk($scanner->ip_address, $scanner->port),
                'tadphp' => new TadPhp($scanner->ip_address, $scanner->port),
                default => null,
            };
        });
    }
}
