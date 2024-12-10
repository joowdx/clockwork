<?php

namespace App\Jobs;

use App\Actions\CertifyTimesheet;
use App\Actions\SignAccomplishment;
use App\Enums\AttachmentClassification;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        private array $data = [],
    ) {
        $this->queue = 'main';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timesheets = Timesheet::with(['employee', 'accomplishment', 'export.signers'])
            ->find($this->timesheets);

        $skipped = $timesheets->filter(function (Timesheet $timesheet) {
            return $timesheet->export?->signers?->contains(fn ($sign) => $sign->meta === $this->level) ?? true;
        });

        $signed = $timesheets->reject(function (Timesheet $timesheet) {
            return $timesheet->export?->signers?->contains(fn ($sign) => $sign->meta === $this->level);
        });

        $signed->each(function (Timesheet $timesheet) {
            match ($this->level) {
                'employee' => $this->employee($timesheet),
                default => $this->superior($timesheet),
            };
        });

        $this->notify($signed, $skipped);
    }

    protected function superior(Timesheet $timesheet): void
    {
        DB::transaction(function () use ($timesheet) {
            $user = User::find($this->user);

            app(CertifyTimesheet::class)($timesheet, $user, level: $this->level);

            app(SignAccomplishment::class)($timesheet->accomplishment, $user);
        });
    }

    protected function employee(Timesheet $timesheet): void
    {
        try {
            DB::beginTransaction();

            $employee = Employee::find($this->user);

            $certifier = app(CertifyTimesheet::class);

            $accomplisher = app(SignAccomplishment::class);

            $timesheet = $certifier($timesheet, $employee, $this->data);

            $month = Carbon::parse($timesheet->month);

            $period = match ($timesheet->span) {
                '1st' => $month->format('Y m ').'01-15',
                '2nd' => $month->format('Y m ').'16-'.$month->daysInMonth(),
                default => $month->format('Y m ').'01-'.$month->daysInMonth(),
            };

            $filename = "timesheets/{$timesheet->employee->full_name}/{$month->format('Y/Y m M')}/attachments/(Accomplishment {$period}).pdf";

            $attachment = $timesheet->accomplishment()->create([
                'filename' => $filename,
                'classification' => AttachmentClassification::ACCOMPLISHMENT,
                'disk' => 'azure',
                'context' => [
                    'period' => $timesheet->span,
                ],
            ]);

            Storage::disk('azure')->put($filename, base64_decode($this->data['accomplishment']));

            $accomplisher($attachment, $employee);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            if (@$attachment->export->filename && Storage::disk('azure')->exists($attachment->export->filename)) {
                Storage::disk('azure')->delete($attachment->export->filename);
            }

            if (app()->isLocal()) {
                throw $exception;
            }

            Notification::make()
                ->title('Timesheet certification failed')
                ->body('An error occurred while certifying your timesheet. Please try again later.')
                ->success()
                ->sendToDatabase($employee, true);
        }
    }

    protected function notify($signed = null, $skipped = null): void
    {
        $body = match ($this->level) {
            'employee' => str($signed->first()?->period),
            default => str(<<<HTML
                    <b>Verified ({$signed->count()}):</b> <br>
                    <ul>:verified</ul>
                    <br>
                    <b>Skipped ({$skipped->count()}):</b> <br>
                    <ul>:skipped</ul>
                HTML)
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
        };

        Notification::make()
            ->title($this->level !== 'employee' ? 'Timesheets verified' : 'Timesheets certified')
            ->body($body->toHtmlString())
            ->success()
            ->sendToDatabase(($this->level === 'employee' ? 'App\Models\Employee' : 'App\Models\User')::find($this->user), true);
    }
}
