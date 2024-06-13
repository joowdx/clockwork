<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class ProcessTimesheet implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly Carbon $month;

    private readonly bool $ignore;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Employee $employee,
        Carbon|string $month,
    ) {
        $this->queue = 'main';

        $this->month = is_string($month)
            ? Carbon::parse($month)->startOfMonth()
            : $month;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->employee->id.'-'.$this->month->format('Y-m');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $schedules = Schedule::search(
            employee: $this->employee,
            date: $this->month->clone()->startOfMonth(),
            until: $this->month->clone()->endOfMonth(),
        );

        $sheet = $this->employee->timesheets()->firstOrCreate(['month' => $this->month->startOfMonth()]);

        $sheet->timetables()->delete();

        $time = function (string $week) use ($schedules) {
            return match(true) {
                $schedules?->$week?->count() === 1 => $schedules?->$week?->first()->time,
                $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->count() === 1 =>
                    $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->first()?->time,
                default => 'As required'
            };
        };

        $sheet->update([
            'details' => [
                'supervisor' => $this->employee->currentDeployment?->supervisor?->titled_name,
                'head' => $this->employee->currentOffice?->head?->id !== $this->employee->id ? $this->employee->currentOffice?->head?->titled_name : '',
                'schedule' => ['weekdays' => $time('weekdays'), 'weekends' => $time('weekends')],
            ],
        ]);

        $days = $this->month->range($this->month->clone()->endOfMonth());

        if ($this->job->getConnectionName() === 'sync') {
            collect($days)
                // TODO
                // ADD GROUPING LOGIC HERE FOR CUSTOM SCHEDULES (CONTINUOUS MULTIPLE-DAY SPAN SHIFTS)
                ->each(fn ($day) => ProcessTimetable::dispatchSync($this->employee, $day));
        } else {
            $jobs = collect($days)
                // TODO
                // ADD GROUPING LOGIC HERE FOR CUSTOM SCHEDULES (CONTINUOUS MULTIPLE-DAY SPAN SHIFTS)
                ->map(fn ($day) => new ProcessTimetable($this->employee, $day));

            Bus::batch($jobs->all())
                ->catch(fn () => $sheet->delete())
                ->onQueue('main')
                ->dispatch();
        }

    }
}
