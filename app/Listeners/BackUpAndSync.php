<?php

namespace App\Listeners;

use App\Contracts\BackupRepository;
use App\Events\EmployeesImported;
use App\Events\TimeLogsProcessed;

class BackUpAndSync
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private BackupRepository $repository,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EmployeesImported|TimeLogsProcessed  $event
     * @return void
     */
    public function handle(EmployeesImported|TimeLogsProcessed $event)
    {
        $this->repository->sync($event->user);
    }
}
