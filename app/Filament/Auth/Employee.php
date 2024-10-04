<?php

namespace App\Filament\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class Employee extends \Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.auth.login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        if (Auth::check()) {
            $user = User::find(Auth::id());

            $route = match (true) {
                $user instanceof Employee => route('filament.employee.resources.timesheets.index'),
                $user->hasAnyRole(UserRole::ROOT, UserRole::SUPERUSER) => url(str(settings('superuser') ?: 'superuser')->slug()),
                $user->hasRole(UserRole::EXECUTIVE) => url(str(settings('executive') ?: 'executive')->slug()),
                $user->hasRole(UserRole::BUREAUCRAT) => url(str(settings('bureaucrat') ?: 'bureaucrat')->slug()),
                $user->hasRole(UserRole::DIRECTOR) => url(str(settings('director') ?: 'director')->slug()),
                $user->hasRole(UserRole::MANAGER) => url(str(settings('manager') ?: 'manager')->slug()),
                $user->hasRole(UserRole::SECRETARY) => url(str(settings('secretary') ?: 'secretary')->slug()),
                $user->hasRole(UserRole::SECURITY) => url(str(settings('security') ?: 'security')->slug()),
                default => route('filament.app.pages.dashboard'),
            };

            redirect()->intended($route);
        }

        $this->form->fill();
    }

    public function getHeading(): string|Htmlable
    {
        return str(<<<'HTML'
            <span class="text-sm">Employee</span> <br> Sign in
        HTML)->toHtmlString();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return config('app.name');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Email')
            ->type('text')
            ->required(false)
            ->markAsRequired()
            ->hintAction($this->getPasswordSetupAction())
            ->rule('required');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required(false)
            ->markAsRequired()
            ->rule('required');
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent();
    }

    protected function getPasswordSetupAction(): Action
    {
        $lacking = function ($employee, $fail, $message, $boolean = false) {
            $employee = $employee instanceof \App\Models\Employee ? $employee : \App\Models\Employee::find($employee);

            $lacking = $employee?->offices->isEmpty() || empty($employee?->birthdate) || empty($employee?->sex);

            if ($lacking && $boolean) {
                return $fail($message);
            }
        };

        $confirmation = function ($data, $fail, $success = false) {
            if ($data[0] !== null && (is_null($data[1]) || is_null($data[2]) || is_null($data[3]))) {
                return false;
            }

            $employee = \App\Models\Employee::find($data[0]);

            if (is_null($fail) && is_null($employee)) {
                return false;
            }

            if (is_null($employee)) {
                $fail('Employee not found.');
            }

            if (
                ! $employee->birthdate->isSameDay($data[1] ?? '') ||
                ! $employee->offices->pluck('id')->contains($data[3]) ||
                $employee->sex !== $data[2]
            ) {
                return $fail('Data does not match the record.');
            }

            if (! empty($employee->password) && $success) {
                return $fail('Employee account has already been set up.');
            }
        };

        $action = Action::make('Setup Account')
            ->successNotificationTitle('Account setup successful')
            ->requiresConfirmation()
            ->modalHeading('Setup Account')
            ->modalDescription(null)
            ->modalWidth('xl')
            ->modalIcon('heroicon-o-shield-check')
            ->modalCancelActionLabel('Cancel')
            ->closeModalByClickingAway(false)
            ->slideOver()
            ->form([
                Wizard::make([
                    Step::make('Confirmation')
                        ->description('Confirm your identity')
                        ->schema([
                            Select::make('employee')
                                ->label('Employee')
                                ->placeholder('Select employee')
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn (Set $set) => $set('password', null))
                                ->getSearchResultsUsing(function (string $search) {
                                    return \App\Models\Employee::query()
                                        ->where('name', 'ilike', "%$search%")
                                        ->orWhere('full_name', 'ilike', "%$search%")
                                        ->pluck('name', 'id')
                                        ->take(5);
                                })
                                ->rule(fn (Get $get) => function ($a, $v, $f) use ($get, $confirmation, $lacking) {
                                    if ($get('birthdate') !== null && $get('sex') !== null && $get('office') !== null) {
                                        $confirmation([$v, $get('birthdate'), $get('sex'), $get('office')], $f, 1);
                                    }

                                    $lacking($v, $f, 'Employee data is incomplete.');
                                }),
                            DatePicker::make('birthdate')
                                ->required()
                                ->rule('date')
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $fail)),
                            Select::make('sex')
                                ->required()
                                ->in(['male', 'female'])
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ])
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $fail)),
                            Select::make('office')
                                ->required()
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    return \App\Models\Office::query()
                                        ->where('name', 'ilike', "%$search%")
                                        ->orWhere('code', 'ilike', "%$search%")
                                        ->pluck('code', 'id')
                                        ->take(5);
                                })
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $fail)),
                        ]),
                    Step::make('Authentication')
                        ->description('Set your login credentials')
                        ->schema([
                            TextInput::make('email')
                                ->markAsRequired()
                                ->rules(['required', 'email:strict,dns,spoof,filter'])
                                ->rule(Password::default()->uncompromised())
                                ->rule(fn (Get $get) => Rule::unique('employees', 'email')->ignore($get('employee'))),
                            TextInput::make('password')
                                ->label('New Password')
                                ->validationAttribute('new password')
                                ->password()
                                ->same('password_confirmation')
                                ->markAsRequired()
                                ->rule('required')
                                ->live()
                                ->rule(Password::default()->uncompromised()),
                            TextInput::make('password_confirmation')
                                ->label('Confirm password')
                                ->password()
                                ->markAsRequired()
                                ->rule('required'),
                        ]),
                ]),
            ])
            ->action(function (Action $action, array $data) {
                \App\Models\Employee::find($data['employee'])->update([
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);

                $action->sendSuccessNotification();
            });

        return $action;
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            isset($data['email']) ? 'email' : 'id' => $data['email'] ?? $data['id'],
            'password' => @$data['password'],
        ];
    }

    public function adminAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('register')
            ->link()
            ->label(__('here...'))
            ->extraAttributes(['class' => 'italic'])
            ->url(route('filament.app.auth.login'));
    }
}
