<?php

namespace App\Providers;

use App\Models\Scanner;
use App\Services\DownloaderService;
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
        config(['app.initiated' => now()]);

        $this->app->bind(DownloaderService::class, function ($app) {
            $scanner = $app->request->route('scanner') ?? $app->request->scanner ?? $app->request->scanner_id;

            if (! $scanner instanceof Scanner) {
                $scanner = Scanner::find($scanner);
            }

            return $scanner ? new DownloaderService($scanner) : null;
        });
    }
}
