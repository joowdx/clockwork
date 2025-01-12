<?php

namespace App\Filament\Auth;

use App\Http\Responses\LoginResponse;
use App\Traits\CanSendEmailVerification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Models\Contracts\FilamentUser;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Pages\Auth\Login
{
    use CanSendEmailVerification;

    protected static string $layout = 'filament-panels::components.layout.base';

    protected static string $view = 'filament.auth.login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        if (Auth::guard('employee')->check()) {
            redirect()->route('filament.employee.resources.timesheets.index');
        }

        $this->form->fill();
    }

    public function homeAction(): Action
    {
        return Action::make('go-home')
            ->link()
            ->label('back to home')
            ->icon(match (__('filament-panels::layout.direction')) {
                'rtl' => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login.rtl') ?? 'heroicon-m-arrow-right',
                default => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login') ?? 'heroicon-m-arrow-left',
            })
            ->url('/');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (
            ! Auth::guard($data['login_as'])
                ->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)
        ) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function socialite(string $provider)
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $guard = match ($this->form->getRawState()['login_as'] ?? null) {
            'employee' => 'employee',
            default => 'web',
        };

        return redirect()->route('socialite.filament.auth.oauth.redirect', [
            'provider' => $provider, 'guard' => $guard,
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
            ActionGroup::make([
                $this->getSocialiteLoginFormAction('google'),
                $this->getSocialiteLoginFormAction('microsoft'),
            ])
                ->hidden()
                ->button()
                ->label('More options')
                ->color('gray'),
        ];
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getAuthenticationOptionFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getSocialiteLoginFormAction(string $provider): Action
    {
        return Action::make($provider)
            ->icon("fab-$provider")
            ->action("socialite('$provider')");
    }

    protected function getAuthenticationOptionFormComponent()
    {
        return Radio::make('login_as')
            ->inline()
            ->inlineLabel(false)
            ->live()
            ->default(fn () => session()->get('guard') ?? 'web')
            ->required()
            ->options([
                'web' => 'Administrator',
                'employee' => 'Employee',
            ])
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required(false)
            ->markAsRequired()
            ->rule('required')
            ->autocomplete()
            ->autofocus()
            ->label(fn (Get $get) => $get('login_as') === 'web' ? 'Username' : 'Email')
            ->rule(fn (Get $get) => $get('login_as') !== 'web' ? 'email' : null)
            ->hintAction($this->getAccountSetupAction())
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required(false)
            ->markAsRequired()
            ->rule('required')
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="6"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->extraInputAttributes(['tabindex' => 5]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->extraAttributes(['tabindex' => 7]);
    }

    protected function getAccountSetupAction(): FormAction
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

        $action = FormAction::make('Setup Account')
            ->successNotificationTitle('Account setup successful')
            ->requiresConfirmation()
            ->modalHeading('Setup Account')
            ->modalDescription(null)
            ->modalWidth('xl')
            ->modalIcon('heroicon-o-shield-check')
            ->modalCancelActionLabel('Cancel')
            ->closeModalByClickingAway(false)
            ->extraAttributes(['tabindex' => 4])
            ->slideOver()
            ->disabled(fn (Get $get) => $get('login_as') !== 'employee')
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
            ->action(function (Actions\Action $action, array $data) {
                $employee = \App\Models\Employee::find($data['employee']);

                $data = array_filter(['email' => $data['email'], 'password' => $data['password'] ?? null]);

                $employee->update($data);

                $this->sendEmailVerificationNotification($employee);

                $action->sendSuccessNotification();
            });

        return $action;
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
