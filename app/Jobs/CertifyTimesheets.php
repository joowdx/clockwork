<?php

namespace App\Jobs;

use App\Actions\CertifyTimesheet;
use App\Actions\SignAccomplishment;
use App\Models\Timesheet;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CertifyTimesheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $timesheets,
        private string $level,
        private string $user,
    ) {
        $this->queue = 'main';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timesheets = Timesheet::with(['employee', 'accomplishment', 'signers'])
            ->find($this->timesheets);

        $skipped = $timesheets->filter(function (Timesheet $timesheet) {
            return $timesheet->signers->contains(fn ($sign) => $sign->meta === $this->level);
        });

        $signed = $timesheets->reject(function (Timesheet $timesheet) {
            return $timesheet->signers->contains(fn ($sign) => $sign->meta === $this->level);
        });

        $signed->each(function (Timesheet $timesheet) {
            if ($timesheet->signers->contains(fn ($sign) => $sign->meta === $this->level)) {
                return;
            }

            DB::transaction(function () use ($timesheet) {
                $user = User::find($this->user);

                $panel = $this->level;

                app(CertifyTimesheet::class)($timesheet, $user, level: $panel);

                app(SignAccomplishment::class)($timesheet->accomplishment, $user);
            });
        });

        $this->notify($signed, $skipped);
    }

    protected function notify($signed, $skipped): void
    {
        $body = <<<HTML
            <b>Verified ({$signed->count()}):</b> <br>
            <ul>:verified</ul>
            <br>
            <b>Skipped ({$skipped->count()}):</b> <br>
            <ul>:skipped</ul>
        HTML;

        $body = str($body)
            ->when(
                $signed->isEmpty(),
                fn ($body) => $body->replace(':verified', '<li>None</li>'),
                fn ($body) => $body->replace(
                    ':verified',
                    $signed
                        ->map(fn ($timesheet) => "<li>{$timesheet->employee->name} ({$timesheet->period})</li>")
                        ->implode('')
                )
            )
            ->when(
                $skipped->isEmpty(),
                fn ($body) => $body->replace(':skipped', null)->replace('<b>Skipped (0):</b>', null)->replaceLast('<br>', null),
                fn ($body) => $body->replace(
                    ':skipped',
                    $skipped
                        ->map(fn ($timesheet) => "<li>{$timesheet->employee->name} ({$timesheet->period})</li>")
                        ->implode('')
                )
            )
            ->toHtmlString();

        Notification::make()
            ->title('Timesheets verified')
            ->body($body)
            ->success()
            ->sendToDatabase(User::find($this->user));
    }
}
