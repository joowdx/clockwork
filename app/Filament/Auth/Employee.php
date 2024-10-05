<?php

namespace App\Filament\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Traits\CanSendEmailVerification;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class Employee extends \Filament\Pages\Auth\Login
{
    use CanSendEmailVerification;

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
            ->hintAction($this->getAccountSetupAction())
            ->rules(['required', 'email:strict,rfc,dns,spoof,filter'])
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required(false)
            ->markAsRequired()
            ->rule('required')
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="5"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->extraInputAttributes(['tabindex' => 4]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->extraInputAttributes(['tabindex' => 6]);
    }

    protected function getAccountSetupAction(): Action
    {
        $lacking = function ($employee, $fail = null, $message = '', $boolean = true) {
            $employee = $employee instanceof \App\Models\Employee ? $employee : \App\Models\Employee::find($employee);

            $lacking = $employee?->offices->isEmpty() || empty($employee?->birthdate) || empty($employee?->sex);

            if ($lacking && $boolean) {
                return true;
            }

            if ($lacking && $fail && $message && $boolean === false) {
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
                ! $employee?->birthdate?->isSameDay($data[1] ?? '') ||
                ! $employee?->offices->pluck('id')->contains($data[3]) ||
                $employee?->sex !== $data[2]
            ) {
                return $fail('Data does not match the record.');
            }

            if (! empty($employee->password) && ! empty($employee->email_verified_at) && $success) {
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
            ->extraAttributes(['tabindex' => 3])
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
                                        $message = 'Employee data is incomplete. Please contact the officers in charge.';

                                        $lacking($v, $f, $message, false);

                                        $confirmation([$v, $get('birthdate'), $get('sex'), $get('office')], $f, 1);
                                    }
                                }),
                            DatePicker::make('birthdate')
                                ->required()
                                ->rule('date')
                                ->rule(fn (Get $get) => function ($a, $v, $f) use ($get, $confirmation, $lacking) {
                                    if ($get('birthdate') !== null && $get('sex') !== null && $get('office') !== null && ! $lacking($get('employee'), boolean: true)) {
                                        $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $f);
                                    }
                                }),
                            Select::make('sex')
                                ->required()
                                ->in(['male', 'female'])
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ])
                                ->rule(fn (Get $get) => function ($a, $v, $f) use ($get, $confirmation, $lacking) {
                                    if ($get('birthdate') !== null && $get('sex') !== null && $get('office') !== null && ! $lacking($get('employee'), boolean: true)) {
                                        $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $f);
                                    }
                                }),
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
                                ->rule(fn (Get $get) => function ($a, $v, $f) use ($get, $confirmation, $lacking) {
                                    if ($get('birthdate') !== null && $get('sex') !== null && $get('office') !== null && ! $lacking($get('employee'), boolean: true)) {
                                        $confirmation([$get('employee'), $get('birthdate'), $get('sex'), $get('office')], $f);
                                    }
                                }),
                        ]),
                    Step::make('Authentication')
                        ->description('Set your login credentials')
                        ->schema([
                            TextInput::make('email')
                                ->markAsRequired()
                                ->rule(fn (Get $get) => Rule::unique('employees', 'email')->ignore($get('employee')))
                                ->rules(['required', 'email:strict,dns,spoof,filter']),
                            TextInput::make('password')
                                ->label('New Password')
                                ->validationAttribute('new password')
                                ->password()
                                ->visible(fn (Get $get) => \App\Models\Employee::find($get('employee'))?->password === null)
                                ->same('password_confirmation')
                                ->markAsRequired()
                                ->rule('required')
                                ->live()
                                ->rule(Password::default()->uncompromised()),
                            TextInput::make('password_confirmation')
                                ->label('Confirm password')
                                ->password()
                                ->visible(fn (Get $get) => \App\Models\Employee::find($get('employee'))?->password === null)
                                ->markAsRequired()
                                ->rule('required'),
                        ]),
                ]),
            ])
            ->action(function (Action $action, array $data) {
                $employee = \App\Models\Employee::find($data['employee']);

                $data = array_filter(['email' => $data['email'], 'password' => $data['password'] ?? null]);

                $employee->update($data);

                $this->sendEmailVerificationNotification($employee);

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
            ->url(route('filament.app.auth.login'))
            ->extraAttributes(['tabindex' => 1]);
    }
}
