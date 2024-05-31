<?php

namespace App\Actions;

use App\Jobs\ProcessTimetable;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class ProcessTimesheet
{
    public function __invoke(Employee $employee, Carbon|string $month, ?User $notify = null): void
    {
        $month = Carbon::parse($month)->startOfMonth();

        $sheet = $employee->timesheets()->firstOrCreate(compact('month'));

        $schedules = $employee->schedules()
            ->active($month, $month->clone()->endOfMonth())
            ->get()
            ->merge(Schedule::where('global', true)->active($month, $month->clone()->endOfMonth())->get());

        $tables = $month->range($month->clone()->endOfMonth())->map(fn ($day) => (object) [
            'shift' => $schedules->first(fn (Schedule $schedule) => $schedule->isActive($day)),
            'day' => $day,
        ]);

        $jobs = collect($tables)
            // TODO
            // ADD GROUPING LOGIC HERE FOR CUSTOM SCHEDULES (CONTINUOUS MULTIPLE-DAY SPAN SHIFTS)
            ->map(fn ($table) => new ProcessTimetable($employee, $table->day, $table->shift));

        Bus::batch($jobs->all())
            ->dispatch();
    }
}
