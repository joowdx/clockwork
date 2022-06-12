<?php

namespace App\Providers;

use App\Contracts\UserRepository as UserRepositoryContract;
use App\Contracts\Repository;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TimeLogController;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\TimeLog;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\ScannerRepository;
use App\Repositories\TimeLogRepository;
use App\Services\EmployeeService;
use App\Services\ScannerService;
use App\Services\TimeLogService;
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

        $this->app->when([EmployeeService::class, EmployeeController::class])
            ->needs(Repository::class)
            ->give(fn () => app(EmployeeRepository::class));

        $this->app->when([ScannerService::class, ScannerController::class])
            ->needs(Repository::class)
            ->give(fn () => app(ScannerRepository::class));

        $this->app->when([TimeLogService::class, TimeLogController::class])
            ->needs(Repository::class)
            ->give(fn () => new TimeLogRepository(app(TimeLog::class)));

        $this->app->bind(EmployeeRepository::class, fn () => new EmployeeRepository(app(Employee::class)));

        $this->app->bind(ScannerRepository::class, fn () => new ScannerRepository(app(Scanner::class)));
    }
}
