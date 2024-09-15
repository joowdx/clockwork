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
            $this->user instanceof Employee => 'filament.employee.resources.timesheets.index',
            $this->user->hasAnyRole(UserRole::ROOT, UserRole::SUPERUSER) => 'filament.superuser.pages.dashboard',
            $this->user->hasRole(UserRole::EXECUTIVE) => 'filament.executive.pages.dashboard',
            $this->user->hasRole(UserRole::BUREAUCRAT) => 'filament.bureaucrat.pages.dashboard',
            $this->user->hasRole(UserRole::DIRECTOR) => 'filament.director.pages.dashboard',
            $this->user->hasRole(UserRole::MANAGER) => 'filament.manager.pages.dashboard',
            $this->user->hasRole(UserRole::SECRETARY) => 'filament.secretary.pages.dashboard',
            $this->user->hasRole(UserRole::SECURITY) => 'filament.security.pages.dashboard',
            default => 'filament.app.pages.dashboard',
        };

        return redirect()->intended(route($route));
    }
}
