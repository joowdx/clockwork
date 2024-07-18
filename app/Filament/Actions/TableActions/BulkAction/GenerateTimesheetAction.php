<?php

namespace App\Filament\Actions\TableActions\BulkAction;

use App\Jobs\ProcessTimesheet;
use App\Models\Employee;
use App\Traits\TimelogsHasher;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;

class GenerateTimesheetAction extends BulkAction
{
    use TimelogsHasher;

    public static function make(?string $name = null): static
    {
        $class = static::class;

        $name ??= 'generate-timesheet';

        $static = app($class, ['name' => $name]);

        $static->configure();

        return $static;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-bolt');

        $this->color('gray');

        $this->requiresConfirmation();

        $this->modalIconColor('danger');

        $this->modalDescription($this->generateConfirmation());

        $this->form($this->generateForm());

        $this->action(fn (Collection $records, array $data) => $this->generateAction($records, $data));
    }

    public function generateConfirmation(): string
    {
        return 'Timesheets are automatically generated.
        Only do this when you know what you are doing as this will overwrite the existing timesheet data.
        To proceed, please enter your password.';
    }

    public function generateAction(Collection|Employee $employee, array $data): void
    {
        if ($employee instanceof Employee || $employee->count() === 1) {

            $employee = $employee instanceof Collection ? $employee->first() : $employee;

            ProcessTimesheet::dispatchSync($employee, $data['month']);

            Notification::make()
                ->success()
                ->title("Timesheet for {$employee->name} generated.")
                ->send();

            return;
        }

        $employee->ensure(Employee::class)->load(['timesheets' => fn ($q) => $q->whereDate('month', $data['month'].'-01'), 'timesheets.timelogs']);

        $jobs = $employee
            ->reject(fn ($employee) => $employee->timesheets->first() ? $this->checkDigest($employee->timesheets->first()) : false)
            ->map(fn (Employee $employee) => new ProcessTimesheet($employee, $data['month']));

        Notification::make()
            ->info()
            ->title('Timesheet generation will start shortly')
            ->send();

        $user = auth()->user();

        if ($jobs->isEmpty()) {
            Notification::make()
                ->info()
                ->title('Timesheets are already generated')
                ->body("No timesheets to generate for month {$data['month']} <br>"  . $employee->pluck('name')->sort()->join('<br>'))
                ->sendToDatabase($user);

            return;
        }

        Bus::batch($jobs->all())
            ->then(function () use ($data, $employee, $user) {
                $names = $employee->pluck('name')->sort();

                Notification::make()
                    ->info()
                    ->title('Timesheets are being generated')
                    ->body("<b>({$data['month']})</b> <br> To be generated for (please wait for a while): <br>{$names->join('<br>')}")
                    ->sendToDatabase($user);
            })
            ->catch(function () use ($user) {
                Notification::make()
                    ->error()
                    ->title('Failed to generate timesheets')
                    ->body('Please try again')
                    ->sendToDatabase($user);
            })
            ->onQueue('main')
            ->dispatch();
    }

    public function generateForm(): array
    {
        return [
            TextInput::make('month')
                ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                ->type('month')
                ->required(),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->markAsRequired()
                ->rules([
                    'required',
                    fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                        if ($value === $get('month')) {
                            return;
                        }

                        if (! password_verify($value, auth()->user()->password)) {
                            $fail('The password is incorrect');
                        }
                    },
                ]),
        ];
    }
}
