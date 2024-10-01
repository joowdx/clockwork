<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    protected Employee|User $user;

    public function toResponse($request): RedirectResponse|Redirector
    {
        $this->user = $request->user() ?? $request->user('employee');

        $route = match (true) {
            $this->user instanceof Employee => route('filament.employee.resources.timesheets.index'),
            $this->user->hasAnyRole(UserRole::ROOT, UserRole::SUPERUSER) => url(str(settings('superuser') ?: 'superuser')->slug()),
            $this->user->hasRole(UserRole::EXECUTIVE) => url(str(settings('executive') ?: 'executive')->slug()),
            $this->user->hasRole(UserRole::BUREAUCRAT) => url(str(settings('bureaucrat') ?: 'bureaucrat')->slug()),
            $this->user->hasRole(UserRole::DIRECTOR) => url(str(settings('director') ?: 'director')->slug()),
            $this->user->hasRole(UserRole::MANAGER) => url(str(settings('manager') ?: 'manager')->slug()),
            $this->user->hasRole(UserRole::SECRETARY) => url(str(settings('secretary') ?: 'secretary')->slug()),
            $this->user->hasRole(UserRole::SECURITY) => url(str(settings('security') ?: 'security')->slug()),
            default => 'filament.app.pages.dashboard',
        };

        return redirect()->intended($route);
    }
}
