<?php

namespace App\Filament\Actions\TableActions;

use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use LSNepomuceno\LaravelA1PdfSign\Sign\SignaturePdf;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;

class CertifyTimesheetAction extends Action
{
    protected string|false|null $level = null;

    protected function setUp(): void
    {
        $this->name ??= 'certify-timesheet';

        $this->level = match (Filament::getCurrentPanel()->getId()) {
            'director' => 'head',
            'supervisor' => 'supervisor',
            'employee' => null,
            default => false,
        };

        $this->label(in_array($this->level, ['head', 'supervisor']) ? 'Verify' : 'Certify');

        $this->icon('gmdi-fact-check-o');

        $this->requiresConfirmation();

        $this->modalIcon('gmdi-fact-check-o');

        $this->successNotificationTitle('Timesheet ' . ($this->level === null ? 'certified' : 'verified'));

        $this->hidden(function (Timesheet $record) {
            if ($this->level === false) {
                return true;
            }

            if ($this->level === null) {
                return $record->certified['1st'] && $record->certified['2nd'] || $record->certified['full'];
            }

            return match ($this->level) {
                'head' => $record->certified['1st'] && @$record->firstHalfExportable->details->verification->head->at ||
                    $record->certified['2nd'] && @$record->secondHalfExportable->details->verification->head->at ||
                    $record->certified['full'] && @$record->fullMonthExportable->details->verification->head->at,
                'supervisor' => $record->certified['1st'] && @$record->firstHalfExportable->details->verification->supervisor->at ||
                    $record->certified['2nd'] && @$record->secondHalfExportable->details->verification->supervisor->at ||
                    $record->certified['full'] && @$record->fullMonthExportable->details->verification->supervisor->at,
                default => false,
            };
        });

        $this->modalDescription(function (Timesheet $record) {
            $month = Carbon::parse($record->month);

            $prompt = <<<HTML
                Certify {$month->format('F Y')} timesheet information. <br>

                <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                    Proceeding will overwrite existing digital signature applied (system limitation)
                </span>
            HTML;

            return str($prompt)
                ->ucfirst()
                // ->replace(':replace', $this->level === null ? "; after that, you will not be able to make any further changes." : '.')
                ->toHtmlString();
        });

        $this->form([
            Select::make('period')
                ->required()
                ->multiple()
                ->options([
                    '1st' => '1st half',
                    '2nd' => '2nd half',
                    'full' => 'Full month',
                ])
                ->dehydratedWhenHidden()
                ->disableOptionWhen(function (Timesheet $record, ?string $value) {
                    if (in_array(Filament::getCurrentPanel()->getId(), ['director', 'manager'])) {
                        return match ($value) {
                            'full' => ! ($record->certified['1st'] && $record->certified['2nd'] || $record->certified['full']),
                            '1st' => ! $record->certified['1st'],
                            '2nd' => ! $record->certified['2nd'],
                        };
                    }
                })
                ->rule(fn (Timesheet $record) => function ($attribute, $value, $fail) use ($record) {
                    if (Carbon::parse($record->month)->setDay(15)->endOfDay()->gte(now()) && in_array('1st', $value)) {
                        return $fail('First half of the month is not yet certifiable since it has yet to end.');
                    }

                    if (today()->startOfMonth()->isSameDay($record->month) && (in_array('full', $value) || in_array('2nd', $value))) {
                        return $fail('Full month or second half of the month not yet certifiable since it has yet to end.');
                    }

                    if (in_array('full', $value) && count($value) > 1) {
                        return $fail('You can only certify in either full month or both halves of the month.');
                    }

                    if ($this->level === null) {
                        if (in_array('full', $value) && $record->certified['full']) {
                            return $fail('Full month is already certified.');
                        }

                        if (in_array('full', $value) && ($record->certified['1st'] || $record->certified['2nd'])) {
                            return $fail(($record->certified['1st'] ? 'First' : 'Second').' half of the month is already certified. ');
                        }

                        if (in_array('1st', $value) && $record->certified['1st']) {
                            return $fail('First half of the month is already certified.');
                        }

                        if (in_array('2nd', $value) && $record->certified['2nd']) {
                            return $fail('Second half of the month is already certified.');
                        }
                    }

                    if ($this->level === 'head') {
                        if (in_array('full', $value) && $record->certified['full'] && @$record->fullMonthExportable->details->head) {
                            return $fail('Full month is already verified.');
                        }

                        if (in_array('1st', $value) && $record->certified['1st'] && @$record->firstHalfExportable->details->head) {
                            return $fail('First half of the month is already verified.');
                        }

                        if (in_array('2nd', $value) && $record->certified['2nd'] && @$record->secondHalfExportable->details->head) {
                            return $fail('Second half of the month is already verified.');
                        }
                    }
                }),
            Checkbox::make('confirmation')
                ->label(fn () => 'I '.(in_array($this->level, ['head', 'supervisor']) ? 'verify' : 'certify').' that the information is accurate and correct report of the hours of work performed.')
                ->markAsRequired()
                ->accepted()
                ->rule(fn () => function ($attribute, $value, $fail) {
                    /** @var \App\Models\User|\App\Models\Employee */
                    $user = Auth::user();

                    if ($user->signature === null) {
                        return $fail('You must have a signature to certify.');
                    }
                })
                ->validationMessages(['accepted' => 'You must '.(in_array($this->level, ['head', 'supervisor']) ? 'verify' : 'certify').' first.']),
        ]);

        $this->action(function (self $action, Timesheet $timesheet, array $data) {
            if ($this->level === false) {
                return;
            }

            /** @var \App\Models\User|\App\Models\Employee */
            $user = Auth::user();

            $level = match (Filament::getCurrentPanel()->getId()) {
                'director' => 'head',
                'supervisor' => 'supervisor',
                default => 'employee',
            };

            foreach ($data['period'] as $period) {
                match ($period) {
                    '1st' => $timesheet->setFirstHalf(),
                    '2nd' => $timesheet->setSecondHalf(),
                    default => $timesheet->setFullMonth(),
                };

                $filename = trim("{$timesheet->month}-{$period}".($period !== 'full' ? '-hlf' : '')."-{$timesheet->employee->name}", '.').'.pdf';

                $timestamp = now();

                if ($level === 'employee') {
                    $certification = strtolower(str()->ulid());

                    $exportable = $this->generate($timesheet, $period, $certification, $timestamp);

                    $exportable = $user->signature?->certificate
                        ? $this->sign($exportable, $level, $timestamp->format('Y-m-d H:i:s'))
                        : base64_decode($exportable);

                    $export = $timesheet->exports()->create([
                        'filename' => $filename,
                        'content' => $exportable,
                    ]);

                    $export->forceFill([
                        'id' => $certification,
                        'details->period' => $period,
                        'details->certification->at' => $timestamp->format('Y-m-d H:i:s'),
                    ])->save();
                } elseif (in_array($level, ['supervisor', 'head'])) {
                    $export = $timesheet->exports()->where('details->period', $period)->first();

                    $exportable = base64_encode($export->content);

                    $exportable = $this->sign($exportable, $level, $timestamp->format('Y-m-d H:i:s'));

                    $export->forceFill([
                        'content' => $exportable,
                        "details->verification->{$level}->at" => now()->format('Y-m-d H:i:s'),
                    ])->save();
                }
            }

            // $action->sendSuccessNotification();

            Notification::make()
                ->success()
                ->title($action->getSuccessNotificationTitle())
                ->send();
        });
    }

    protected function generate(Timesheet $timesheet, string $period, string $export)
    {
        /** @var \App\Models\User|\App\Models\Employee */
        $user = Auth::user();

        $data = [
            'timesheets' => [$timesheet],
            'user' => $user,
            'month' => $timesheet->month,
            'period' => $period,
            'format' => 'csc',
            'size' => 'folio',
            'certify' => $export,
            'misc' => [
                'calculate' => true,
            ],
        ];

        $pdf = Pdf::view('print.csc', $data)
            ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']))
            ->paperSize(8.5, 13, 'in');

        return $pdf->base64();
    }

    protected function sign(string $data, string $level, $timestamp): string
    {
        /** @var \App\Models\User|\App\Models\Employee */
        $user = Auth::user();

        $pdf = sys_get_temp_dir().'/'.uniqid().'.pdf';

        file_put_contents($pdf, base64_decode($data));

        $signature = $user->signature;

        $image = Image::make(storage_path('app/'.$signature->specimen));

        $specimen = sys_get_temp_dir().'/'.uniqid().'.png';

        $image->resize(400, 300, function ($constraint) {
            $constraint->aspectRatio();
        });

        $image->text('Digitally signed', $image->width() * 0.75, $image->height() * 0.20, function ($font) {
            $font->file(resource_path('fonts/roboto.ttf'));
            $font->size(16);
            $font->align('center');
            $font->valign('middle');
        });

        $image->text($timestamp, $image->width() * 0.75, $image->height() * 0.20 + 16, function ($font) {
            $font->file(resource_path('fonts/roboto.ttf'));
            $font->size(16);
            $font->align('center');
            $font->valign('middle');
        });

        $image->save($specimen);

        $certificate = (new ManageCert)->setPreservePfx()->fromPfx(storage_path('app/'.$signature->certificate), $signature->password);

        $y = match ($level) {
            'employee' => 238,
            'head' => 280,
            'supervisor' => 265,
        };

        try {
            return (new SignaturePdf($pdf, $certificate))
                ->setImage(
                    $specimen,
                    pageX: 90,
                    pageY: $y,
                    imageH: $signature->portrait || ! $signature->landscape ? 35 : 20,
                    imageW: 0,
                )
                ->setInfo(
                    name: $user->name,
                    reason: in_array($level, ['supervisor', 'head']) ? 'Verification' : 'Certification',
                    location: 'Philippines',
                )
                ->signature();
        } finally {
            if (file_exists($pdf)) {
                unlink($pdf);
            }

            if (file_exists($specimen)) {
                unlink($specimen);
            }
        }
    }
}
