<?php

namespace App\Actions;

use App\Enums\TimesheetCoordinates;
use App\Enums\TimesheetPeriod;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;

class CertifyTimesheet
{
    public function __invoke(Timesheet $timesheet, User|Employee $user, array $data = [], ?string $level = null)
    {
        return $this->certify($timesheet, $user, $data, $level);
    }

    public function certify(Timesheet $timesheet, User|Employee $user, array $data = [], $level = null)
    {
        $level ??= 'employee';

        throw_unless(in_array($level, ['employee', 'leader', 'director']), 'InvalidArgumentException', 'Unknwown timesheet certification level.');

        throw_unless(in_array($data['period'] ?? null, [null, 'full', '1st', '2nd']) || $data['period'] instanceof TimesheetPeriod, 'InvalidArgumentException', 'Unknwown timesheet period.');

        throw_if($user instanceof Employee && in_array($level, ['leader', 'director']), 'InvalidArgumentException', 'Invalid level for user.');

        throw_if($user instanceof User && $level === 'employee', 'InvalidArgumentException', 'Invalid level for user.');

        $out = sys_get_temp_dir().'/'.uniqid().'.pdf';

        DB::transaction(function () use ($timesheet, $user, $data, $level, $out) {
            $month = Carbon::parse($timesheet->month);

            $period = ! (! isset($data['period']) || is_null($data['period']))
                ? ($data['period'] instanceof TimesheetPeriod ? $data['period']->value : $data['period'] ?? 'full')
                : null;

            if ($level === 'employee') {
                $timesheet = match ($period) {
                    'full' => $timesheet,
                    default => $timesheet->timesheets()
                        ->firstOrCreate([
                            'timesheet_id' => $timesheet->id,
                            'span' => $period,
                        ], [
                            'month' => "{$timesheet->month}-01",
                            'employee_id' => $timesheet->employee_id,
                        ]),
                };

                $timesheet->update(['details' => $this->details($timesheet, $period)]);

                $period = match ($timesheet->span) {
                    '1st' => $month->format('Y m ').'01-15',
                    '2nd' => $month->format('Y m ').'16-'.$month->daysInMonth(),
                    default => $month->format('Y m ').'01-'.$month->daysInMonth(),
                };

                $path = "timesheets/{$month->format('Y/Y m M')}/{$timesheet->employee->full_name} ({$period}).pdf";
            }

            $file = match ($level) {
                'leader', 'director' => $timesheet->export->content,
                default => $this->generate($timesheet, $user, $data, $path),
            };

            $this->sign($user, $file, $out, $level);

            $timesheet->update([
                'exported_at' => now(),
            ]);

            $timesheet->export->update([
                'digest' => hash('sha512', $timesheet->export->content),
            ]);

            $timesheet->export->signers()->create([
                'meta' => $level,
                'signer_type' => $user::class,
                'signer_id' => $user->id,
            ]);

            Storage::disk('azure')->put($timesheet->export->filename, file_get_contents($out));
        });
    }

    protected function sign(User|Employee $user, string $file, string $out, $level): void
    {
        $pdf = sys_get_temp_dir().'/'.uniqid().'.pdf';

        file_put_contents($pdf, $file);

        $field = match ($level) {
            'employee' => 'employee-field',
            'leader' => 'supervisor-field',
            'director' => 'head-field',
        };

        $coordinates = match ($level) {
            'employee' => TimesheetCoordinates::FOLIO_EMPLOYEE,
            'leader' => TimesheetCoordinates::FOLIO_SUPERVISOR,
            'director' => TimesheetCoordinates::FOLIO_HEAD,
        };

        $reason = match ($level) {
            'employee' => 'Employee timesheet certification',
            'leader' => 'Immediate supervisor timesheet verification',
            'director' => 'Office head timesheet approval',
        };

        (new SignPdfAction)($user, $pdf, $out, $field, $coordinates, 1, ['reason' => $reason]);
    }

    protected function generate(Timesheet $timesheet, User|Employee $user, array $data, string $path): string
    {
        $data = [
            ...$data,
            'timesheets' => [$timesheet],
            'user' => $user,
            'month' => $timesheet->month,
            'period' => $data['period'] instanceof TimesheetPeriod ? $data['period']->value : $data['period'] ?? 'full',
            'format' => 'csc',
            'size' => $data['size'] ?? 'folio',
            'certify' => 1,
            'misc' => [
                'calculate' => true,
            ],
        ];

        $pdf = Pdf::view('print.csc', $data);

        if (env('APP_ENV') === 'local' && get_current_user() === 'root') {
            $pdf->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']));
        }

        match ($data['size'] ?? 'folio') {
            'folio' => $pdf->paperSize(8.5, 13, 'in'),
            default => $pdf->format($data['size']),
        };

        $timesheet->export()->create([
            'filename' => $path,
            'disk' => 'azure',
            'details' => [
                'period' => $timesheet->span,
            ],
        ]);

        return base64_decode($pdf->base64());
    }

    protected function details(Timesheet $timesheet, string $period)
    {
        $month = Carbon::parse($timesheet->month);

        $schedules = Schedule::search(
            employee: $timesheet->employee,
            date: $month->clone()->setDay($period === '2nd' ? 16 : 1),
            until: $month->clone()->setDay($period === '1st' ? 15 : $month->daysInMonth())->endOfDay(),
        );

        $time = function (string $week) use ($schedules) {
            return match (true) {
                $schedules?->$week?->count() === 1 => $schedules?->$week?->first()->time,
                $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->count() === 1 => $schedules?->$week?->filter(fn ($schedule) => $schedule->{str($week)->singular()->toString()})->first()?->time,
                default => 'as required'
            };
        };

        return [
            'supervisor' => $timesheet->employee->supervisor?->titled_name,
            'head' => $timesheet->employee->currentOffice?->head?->id !== $timesheet->employee->id ? $timesheet->employee->currentOffice?->head?->titled_name : '',
            'schedule' => ['weekdays' => $time('weekdays'), 'weekends' => $time('weekends')],
        ];
    }
}
