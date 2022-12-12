<?php

namespace App\Providers;

use App\Contracts\Import;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Repositories\EmployeeRepository;
use App\Repositories\TimeLogRepository;
use App\Services\EmployeeService;
use App\Services\TimeLogService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(EmployeeService::class, fn () => new EmployeeService(new EmployeeRepository(app(Employee::class))));

        $this->app->bind(Import::class, function () {
            switch(explode('.', Route::currentRouteName())[0]) {
                case 'employees':
                    return new EmployeeService(new EmployeeRepository(app(Employee::class)));
                    break;

                case 'timelogs':
                    return new TimeLogService(new TimeLogRepository(app(TimeLog::class)));
                    break;

                default:
                    throw new BindingResolutionException('Provider not found!');
            }
        });
    }
}
