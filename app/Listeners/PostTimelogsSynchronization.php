<?php

namespace App\Listeners;

use App\Events\TimelogsFlushed;
use App\Events\TimelogsSynchronized;
use App\Jobs\ProcessTimesheet;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class PostTimelogsSynchronization
{
    public function handle(TimelogsSynchronized|TimelogsFlushed $event): void
    {
        /**
         * MAKE TIMETABLES FOR EACH TIMESHEET PERIOD FOR EACH EMPLOYEE
         *
         *
         * UPDATE AFFECTED TIMETABLE FROM EARLIEST TO LATEST
         */
        if (in_array($event->action, ['fetch', 'import'])) {
            $uids = $event->scanner->timelogs()
                ->reorder()
                ->whereHas('employee')
                ->whereBetween('time', [
                    Carbon::parse($event->earliest)->startOfDay(),
                    Carbon::parse($event->latest)->endOfDay(),
                ])
                ->select('uid')
                ->distinct();

            $employees = Employee::query()
                ->whereHas('enrollments', fn ($q) => $q->where('enrollment.scanner_id', $event->scanner->id)->whereIn('enrollment.uid', $uids))
                ->with([
                    'schedules' => fn ($q) => $q->active($event->earliest, $event->latest),
                    'timelogs' => fn ($q) => $q->whereBetween('time', [$event->earliest, $event->latest]),
                ])
                ->lazyById();

            if ($employees->isEmpty()) {
                return;
            }

            $jobs = $employees->map(function (Employee $employee) use ($event) {
                return new ProcessTimesheet($employee, Carbon::parse($event->month)->startOfMonth());
            });

            Bus::batch($jobs->all())
                ->onQueue('main')
                ->dispatch();
        }
    }
}
