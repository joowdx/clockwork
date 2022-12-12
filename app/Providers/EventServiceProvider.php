<?php

namespace App\Providers;

use App\Events\EmployeesImported;
use App\Events\TimeLogsProcessed;
use App\Listeners\BackUpAndSync;
use App\Listeners\MarkActiveAndInactiveEmployees;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }

    public function listens()
    {
        return [
            ...$this->listen,
            EmployeesImported::class => [
                BackUpAndSync::class,
            ],
            TimeLogsProcessed::class => [
                MarkActiveAndInactiveEmployees::class,
                // BackUpAndSync::class,
            ],
        ];
    }
}
