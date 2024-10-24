<?php

namespace App\Filament\Actions\TableActions;

use App\Actions\CertifyTimesheet;
use App\Models\Timesheet;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CertifyTimesheetAction extends Action
{
    protected string|false|null $level = null;

    protected function setUp(): void
    {
        $this->level = match (Filament::getCurrentPanel()->getId()) {
            'director' => 'head',
            'supervisor' => 'supervisor',
            default => false,
        };

        $this->name('verify-timesheet');

        $this->label('Verify');

        $this->icon('gmdi-fact-check-o');

        $this->modalWidth('max-w-md');

        $this->modalIcon('gmdi-fact-check-o');

        $this->slideOver();

        $this->modalSubmitActionLabel(fn () => $this->getLabel());

        $this->successNotificationTitle('Timesheet successfully verified');

        $this->hidden(function () {
            if ($this->level === false) {
                return true;
            }
        });

        $this->modalDescription(function () {
            $prompt = <<<'HTML'
                <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                    <br> Please review this information thoroughly before proceeding as this action cannot be undone.
                </span>
            HTML;

            return str($prompt)->toHtmlString();
        });

        $this->form(function (Timesheet $record) {
            $preview = ViewField::make('preview')
                ->view('print.csc', [
                    'timesheets' => [$record->setSpan($record->span)],
                    'preview' => true,
                    'overtime' => false,
                ]);

            $confirmation = Checkbox::make('confirmation')
                ->markAsRequired()
                ->accepted()
                ->validationMessages(['accepted' => 'You must verify first.'])
                ->rule(fn () => function ($attribute, $value, $fail) {
                    $user = user();

                    if ($user->signature === null || $user->signature->certificate === null || $user->signature->password === null) {
                        return $fail('You must have to configure your digital signature first.');
                    }
                })
                ->label(function () {
                    $label = strtolower($this->getLabel());

                    return <<<LABEL
                        I {$label} that the information provided is accurate and correctly reports
                        the hours of work performed in accordance with the prescribed office hours
                    LABEL;
                });

            return [$preview, $confirmation];
        });

        $this->action(function (Action $component, CertifyTimesheet $certifier, Timesheet $timesheet, array $data) {
            $certifier($timesheet, Auth::user(), $data, $this->level);

            $user = Auth::user();

            $month = Carbon::parse($timesheet->month);

            $body = <<<HTML
                Timesheet for <b>{$month->format('Y F')}</b> has been successfully verified by {$user->name}.
            HTML;

            Notification::make()
                ->success()
                ->title('Timesheet Verified')
                ->body(str($body)->toHtmlString())
                ->sendToDatabase($timesheet->employee);

            $component->sendSuccessNotification();
        });
    }
}
