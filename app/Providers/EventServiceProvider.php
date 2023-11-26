<?php

namespace App\Providers;

use App\Events\EmployeesImported;
use App\Events\TimelogsProcessed;
use App\Listeners\AddUploadHistory;
use App\Listeners\TimelogsPostProcessor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EmployeesImported::class => [
            AddUploadHistory::class,
        ],
        TimelogsProcessed::class => [
            AddUploadHistory::class,
            TimelogsPostProcessor::class,
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
        return $this->listen;
    }
}
