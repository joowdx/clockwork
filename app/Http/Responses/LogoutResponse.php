<?php

namespace App\Http\Responses;

use App\Models\Employee;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user() ?? $request->user('employee');

        return redirect()->route($user instanceof Employee ? 'filament.employee.auth.login' : 'filament.app.auth.login');
    }
}
