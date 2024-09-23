<?php

namespace App\Filament\Actions\TableActions;

use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CertifyTimesheetAction extends Action
{
    protected string|false|null $level = null;

    protected function setUp(): void
    {
        $this->name ??= 'verify-timesheet';

        $this->level = match (Filament::getCurrentPanel()->getId()) {
            'director' => 'director',
            'employee' => null,
            default => false,
        };

        $this->label('Certify');

        $this->icon('gmdi-fact-check-o');

        $this->requiresConfirmation();

        $this->modalIcon('gmdi-fact-check-o');

        $this->hidden(function (Timesheet $record) {
            if ($this->level === false) {
                return true;
            }

            if ($this->level === null) {
                return $record->certified_full;
            }

            return match ($this->level) {
                'director', 'supervisor' => ! ($record->certified_first || $record->certified_second || $record->certified_full) &&
                    ($record->certificationDetails(level: $this->level === 'director' ? 'head' : 'supervisor')->at || $record->certificationDetails('1st', $this->level === 'director' ? 'head' : 'supervisor')->at || $record->certificationDetails('2nd', $this->level === 'director' ? 'head' : 'supervisor')->at),
                default => false,
            };
        });

        $this->modalDescription(function (Timesheet $record) {
            $countdown = settings('timesheet_verification');

            $month = Carbon::parse($record->month);

            $prompt = <<<HTML
                Certify {$month->format('F Y')} timesheet information. <br>

                <!-- <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                    You will be given $countdown minutes to undo this certification before it is locked and finalized:replace
                </span> -->
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
                ->hidden(function (Timesheet $record) {
                    if (in_array(Filament::getCurrentPanel()->getId(), ['director', 'manager'])) {

                        // return $record->certified_full xor $record->certified_first xor $record->certified_second;
                    }

                    return false;
                })
                ->disableOptionWhen(function (Timesheet $record, Get $get, ?string $value) {
                    if (in_array(Filament::getCurrentPanel()->getId(), ['director', 'manager'])) {
                        return match($value) {
                            'full' => ! $record->certified_full,
                            '1st' => ! $record->certified_first,
                            '2nd' => ! $record->certified_second,
                        };
                    }
                })
                ->rule(fn (Timesheet $record) => function ($attribute, $value, $fail) use ($record) {
                    if (Carbon::parse($record->month)->setDay(15)->endOfDay()->gte(now()) && in_array('1st', $value)) {
                        return $fail("First half of the month is not yet certifiable since it has yet to end.");
                    }

                    if (today()->startOfMonth()->isSameDay($record->month) && (in_array('full', $value) || in_array('2nd', $value))) {
                        return $fail("Full month or second half of the month not yet certifiable since it has yet to end.");
                    }

                    if (in_array('full', $value) && count($value) > 1) {
                        return $fail("You can only certify in either full month or both halves of the month.");
                    }

                    if ($this->level === null) {
                        if (in_array('full', $value) && $record->certified_full) {
                            return $fail("Full month is already certified.");
                        }

                        if (in_array('1st', $value) && $record->certified_first) {
                            return $fail("First half of the month is already certified.");
                        }

                        if (in_array('2nd', $value) && $record->certified_second) {
                            return $fail("Second half of the month is already certified.");
                        }
                    }

                    if ($this->level === 'director') {
                        if (in_array('full', $value) && $record->certificationDetails(level: 'head')->at) {
                            return $fail("Full month is already certified.");
                        }

                        if (in_array('1st', $value) && $record->certificationDetails('1st', 'head')->at) {
                            return $fail("First half of the month is already certified.");
                        }

                        if (in_array('2nd', $value) && $record->certificationDetails('2nd', 'head')->at) {
                            return $fail("Second half of the month is already certified.");
                        }
                    }
                }),
            // TextInput::make('password')
            //     ->markAsRequired()
            //     ->rule('required')
            //     ->password()
            //     ->currentPassword()
            //     ->dehydrated(false),
            Checkbox::make('confirmation')
                ->label('I certify that the information provided for the selected period is accurate and correct report of the hours of work performed.')
                ->markAsRequired()
                ->accepted()
                ->validationMessages(['accepted' => 'You must certify first.']),
        ]);

        $this->action(function (Action $action, Timesheet $timesheet, array $data) {
            if ($this->level === false) {
                return;
            }

            $level = match (Filament::getCurrentPanel()->getId()) {
                'director' => 'head',
                'manager' => 'supervisor',
                default => null,
            };

            foreach ($data['period'] as $period) {
                $timesheet->certifyPeriod($period, $this->level === null ? null : Auth::user(), $level);
            }

            $action->sendSuccessNotification();
        });

        $this->successNotificationTitle('Timesheet certified');
    }
}
