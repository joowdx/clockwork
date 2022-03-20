<?php

namespace App\Providers;

use App\Contracts\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(Repository::class, function () {
            if(app()->runningInConsole()) {
                return null;
            }

            $name = explode('Controller@', preg_replace('/.*\\\/', '', request()->route()->action['controller']))[0];

            $model = 'App\Models\\' . $name;

            try {
                return app("App\Contracts\\{$name}Repository");
            }
            catch (BindingResolutionException) {
                $repository = "App\Repositories\\{$name}Repository";

                return new $repository(new $model);
            }
        });
    }
}
