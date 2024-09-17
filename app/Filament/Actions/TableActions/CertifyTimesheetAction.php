<?php

namespace App\Filament\Actions\TableActions;

use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                'director', 'supervisor' => ! ($record->certified_first || $record->certified_second || $record->certified_full),
                default => false,
            };
        });

        $this->modalDescription(function (Timesheet $record) {
            $countdown = settings('timesheet_verification');

            $month = Carbon::parse($record->month);

            $prompt = <<<HTML
                Certify {$month->format('F Y')} timesheet information. <br>

                <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                    You will be given $countdown minutes to undo this certification before it is locked and finalized:replace
                </span>
            HTML;

            return str($prompt)
                ->ucfirst()
                ->replace(':replace', $this->level === null ? "; after that, you will not be able to make any further changes." : '.')
                ->toHtmlString();
        });

        $this->form([
            Select::make('period')
                ->required()
                ->in(fn (Select $component): array => array_keys($component->getEnabledOptions()))
                ->options([
                    '1st' => '1st half',
                    '2nd' => '2nd half',
                    'full' => 'Full month',
                ])
                ->rule(fn (Timesheet $record) => function ($attribute, $value, $fail) use ($record) {
                    if (Carbon::parse($record->month)->setDay(15)->endOfDay()->gte(now()) && $value === '1st') {
                        return $fail("Selected period not yet verifiable.");
                    }

                    if (today()->startOfMonth()->isSameDay($record->month) && in_array($value, ['full', '2nd'])) {
                        return $fail("Selected period not yet verifiable.");
                    }

                    if ($this->level === null) {
                        if ($value === 'full' && $record->certified_full) {
                            return $fail("Already certified.");
                        }

                        if ($value === '1st' && $record->certified_first) {
                            return $fail("Already certified.");
                        }

                        if ($value === '2nd' && $record->certified_second) {
                            return $fail("Already certified.");
                        }
                    }

                    // if ($this->level === 'director') {
                    //     if ($value === 'full' && $record->certified_full) {
                    //         return $fail("Already certified.");
                    //     }

                    //     if ($value === '1st' && $record->certified_first) {
                    //         return $fail("Already certified.");
                    //     }

                    //     if ($value === '2nd' && $record->certified_second) {
                    //         return $fail("Already certified.");
                    //     }
                    // }
                }),
            TextInput::make('password')
                ->markAsRequired()
                ->rule('required')
                ->password()
                ->currentPassword()
                ->dehydrated(false),
            Checkbox::make('confirmation')
                ->label('I certify that the information provided for the selected period is accurate and correct report of the hours of work performed.')
                ->markAsRequired()
                ->accepted()
                ->validationMessages(['accepted' => 'You must certify first.']),
        ]);

        $this->action(function (Action $action, Timesheet $timesheet, array $data) {
            $panel = Filament::getCurrentPanel()->getId();

            $user = $panel === 'employee' ? null : Auth::user();

            $level = match($panel) {
                'director' => 'head',
                'employee' => null,
                default => false,
            };

            if ($level === false) {
                return;
            }

            $timesheet->certifyPeriod($data['period'], $user, $level);

            $action->sendSuccessNotification();
        });

        $this->successNotificationTitle('Timesheet certified');
    }
}
