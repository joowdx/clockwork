<?php

namespace App\Providers;

use App\Contracts\Repository;
use App\Contracts\UserRepository as UserRepositoryContract;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TimeLogController;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use App\Repositories\TimelogRepository;
use App\Repositories\UserRepository;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use App\Services\TimelogService;
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
        $this->app->bind(UserRepositoryContract::class, fn () => app(UserRepository::class));

        $this->app->when([EmployeeService::class, EmployeeController::class])
            ->needs(Repository::class)
            ->give(fn () => app(EmployeeRepository::class));

        $this->app->when([ScannerService::class, ScannerController::class])
            ->needs(Repository::class)
            ->give(fn () => app(ScannerRepository::class));

        $this->app->when([TimelogService::class, TimeLogController::class])
            ->needs(Repository::class)
            ->give(fn () => app(TimelogRepository::class));

        $this->app->bind(UserRepository::class, fn () => new UserRepository(new User));

        $this->app->bind(EmployeeRepository::class, fn () => new EmployeeRepository(new Employee));

        $this->app->bind(ScannerRepository::class, fn () => new ScannerRepository(new Scanner));

        $this->app->bind(TimelogRepository::class, fn () => new TimelogRepository(new Timelog));
    }
}
