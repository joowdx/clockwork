<?php

namespace App\Listeners;

use App\Events\TimelogsFlushed;
use App\Events\TimelogsSynchronized;
use App\Jobs\ProcessTimetable;
use App\Models\Employee;
use Filament\Notifications\Notification;
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
            $jobs = $event->scanner
                ->timelogs()
                ->whereBetween('time', [
                    Carbon::parse($event->earliest)->startOfDay(),
                    Carbon::parse($event->latest)->endOfDay(),
                ])
                ->selectRaw('DATE(time) as date')
                ->distinct()
                ->reorder()
                ->pluck('date')
                ->flatMap(function ($date) use ($event) {
                    $uids = $event->scanner->timelogs()
                        ->reorder()
                        ->whereHas('employee')
                        ->whereDate('time', $date)
                        ->select('uid')
                        ->distinct();

                    return Employee::query()
                        ->whereHas('enrollments', fn ($q) => $q->where('enrollment.scanner_id', $event->scanner->id)->whereIn('enrollment.uid', $uids))
                        ->lazyById()
                        ->map(fn ($employee) => new ProcessTimetable($employee, Carbon::parse($date)))
                        ->toArray();
                });

            if ($jobs->isEmpty()) {
                return;
            }

            Bus::batch($jobs->all())
                ->onQueue('main')
                ->then(function () {
                    Notification::make()
                        ->success()
                        ->title('Upload successful')
                        ->sendToDatabase(auth()->user());
                })
                ->dispatch();
        }
    }
}
