<?php

namespace App\Filament\Auth;

use App\Enums\UserRole;
use App\Http\Responses\LoginResponse;
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
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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
                $user->hasAnyRole(UserRole::ROOT, UserRole::SUPERUSER) => 'filament.superuser.pages.dashboard',
                $user->hasRole(UserRole::EXECUTIVE) => 'filament.executive.pages.dashboard',
                $user->hasRole(UserRole::BUREAUCRAT) => 'filament.bureaucrat.pages.dashboard',
                $user->hasRole(UserRole::DIRECTOR) => 'filament.director.pages.dashboard',
                $user->hasRole(UserRole::MANAGER) => 'filament.manager.pages.dashboard',
                $user->hasRole(UserRole::SECRETARY) => 'filament.secretary.pages.dashboard',
                $user->hasRole(UserRole::SECURITY) => 'filament.security.pages.dashboard',
                default => 'filament.app.pages.dashboard',
            };

            redirect()->route($route);
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
        return Select::make('id')
            ->label('Employee')
            ->placeholder('Select employee')
            ->searchable()
            ->required()
            ->reactive()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->hintAction(fn (?string $state) => $this->getPasswordSetupAction($state))
            ->afterStateUpdated(fn (Set $set) => $set('password', null))
            ->getSearchResultsUsing(function (string $search) {
                return \App\Models\Employee::query()
                    ->where('name', 'ilike', "%$search%")
                    ->orWhere('full_name', 'ilike', "%$search%")
                    ->pluck('name', 'id')
                    ->take(5);
            });
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required(false)
            ->markAsRequired()
            ->rule('required')
            ->extraInputAttributes(['tabindex' => 2])
            ->visible(fn (Get $get) => ($employee = \App\Models\Employee::find($get('id'))) && ! empty($employee->password));
    }

    protected function getPasswordSetupAction(?string $state): Action
    {
        $employee = \App\Models\Employee::find($state);

        $lacking = $employee?->offices->isEmpty() ||
            empty($employee?->birthdate) ||
            empty($employee?->sex);

        $confirmation = function ($get, $fail) use ($employee) {
            if (is_null($get('birthdate')) || is_null($get('sex')) || is_null($get('office'))) {
                return;
            }

            if (
                ! $employee->birthdate->isSameDay($get('birthdate') ?? '') ||
                ! $employee->offices->pluck('id')->contains($get('office')) ||
                $employee->sex !== $get('sex')
            ) {
                $fail('Data does not match the record.');
            }
        };

        $action = Action::make('Setup password')
            ->visible($employee && empty($employee->password))
            ->successNotificationTitle('Password setup successful')
            ->requiresConfirmation()
            ->modalHeading($lacking ? 'Unable to proceed' : 'Setup Password')
            ->modalDescription($lacking ? 'Your data in the system currently is incomplete. Please ask or contact the administrators to update your data as this is needed for confirmation.' : $employee->titled_name)
            ->modalWidth('xl')
            ->modalCancelActionLabel($lacking ? 'Close' : 'Cancel')
            ->closeModalByClickingAway(false)
            ->form($lacking ? [] : [
                Wizard::make([
                    Step::make('Confirmation')
                        ->description('Confirm your identity')
                        ->schema([
                            DatePicker::make('birthdate')
                                ->required()
                                ->rule('date')
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation($get, $fail)),
                            Select::make('sex')
                                ->required()
                                ->in(['male', 'female'])
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ])
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation($get, $fail)),
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
                                ->rule(fn (Get $get) => fn ($attribute, $value, $fail) => $confirmation($get, $fail)),
                        ]),
                    Step::make('Password')
                        ->description('Set your new password')
                        ->schema([
                            // TextInput::make('pin')
                            //     ->label('New Pin')
                            //     ->password()
                            //     ->markAsRequired()
                            //     ->rule('required'),
                            // TextInput::make('pin_confirmation')
                            //     ->label('Confirm pin')
                            //     ->password()
                            //     ->markAsRequired()
                            //     ->rule('required')
                            //     ->rule('same:pin'),
                            TextInput::make('email')
                                ->markAsRequired()
                                ->unique('employees', 'email')
                                ->rules(['required', 'email:strict,dns,spoof,filter'])
                                ->rule(Password::default()->uncompromised()),
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
            ->action(function (Action $action, array $data) use ($employee) {
                $employee->update([
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);

                $action->sendSuccessNotification();
            });

        if ($lacking) {
            $action->modalSubmitAction(false);
        } else {
            $action->modalIcon('heroicon-o-shield-check');
        }

        return $action;
    }

    protected function throwFailureValidationException($setup = false): never
    {
        throw ValidationException::withMessages([
            'data.id' => $setup ? __('You may need to setup your password first.') : __('filament-panels::pages/auth/login.messages.failed'),
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->visible(fn (Get $get) => ($employee = \App\Models\Employee::find($get('id'))) && ! empty($employee->password))
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            isset($data['username']) ? 'username' : 'id' => $data['username'] ?? $data['id'],
            'password' => @$data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $password = isset($this->form->getState()['password']);

        return $password ? parent::authenticate() : $this->throwFailureValidationException(true);
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
