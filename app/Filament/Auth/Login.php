<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Pages\Auth\Login
{
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

    public function getSubheading(): string|Htmlable|null
    {
        return config('app.name');
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
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required(false)
            ->markAsRequired()
            ->rule('required')
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->extraAttributes(['tabindex' => 5]);
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
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    public function employeeAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('here...'))
            ->extraAttributes(['class' => 'italic'])
            ->url(route('filament.employee.auth.login'))
            ->extraAttributes(['tabindex' => 1]);
    }
}
