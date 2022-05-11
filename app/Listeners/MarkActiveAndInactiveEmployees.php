<?php

namespace App\Listeners;

use App\Events\TimeLogsProcessed;
use App\Services\EmployeeService;

class MarkActiveAndInactiveEmployees
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private EmployeeService $employee,
    ) { }

    /**
     * Handle the event.
     *
     * @param  \App\Events\TimeLogsProcessed  $event
     * @return void
     */
    public function handle(TimeLogsProcessed $event)
    {
        $this->employee->markInactive($event->user);

        $this->employee->markActive($event->user);
    }
}
