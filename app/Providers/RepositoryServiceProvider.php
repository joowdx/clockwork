<?php

namespace App\Providers;

use App\Contracts\BackupRepository;
use App\Contracts\UserRepository as UserRepositoryContract;
use App\Contracts\Repository;
use App\Models\SQLite\Employee;
use App\Models\SQLite\TimeLog;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Repositories\SQLite\EmployeeRepository;
use App\Repositories\SQLite\TimeLogRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
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
        $this->app->bind(UserRepositoryContract::class, fn () => new UserRepository(new User));

        $this->app->bind(EmployeeRepository::class, fn () => new EmployeeRepository(new Employee));

        $this->app->bind(TimeLogRepository::class, fn() => new TimeLogRepository(new TimeLog));

        $this->app->bind(BackupRepository::class, function () {
            switch (@request()->file?->getClientOriginalExtension()) {
                case 'dat': {
                    return app(TimeLogRepository::class);
                }
                case 'csv': {
                    return app(EmployeeRepository::class);
                }
                default: {
                    return null;
                }
            }
        });

        $this->app->bind(Repository::class, function (Application $app) {
            if($app->runningInConsole()) {
                return app(UserRepositoryContract::class);
            }

            $name = explode('Controller@', preg_replace('/.*\\\/', '', request()->route()->action['controller']))[0];

            try {
                return app("App\Contracts\\{$name}Repository");
            }
            catch (BindingResolutionException) {
                $repository = "App\Repositories\\{$name}Repository";

                return new $repository(app("App\Models\\$name"));
            }
        });
    }
}
