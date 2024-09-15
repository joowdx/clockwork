<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as Middleware;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $id = Filament::getCurrentPanel()->getId();

        $current = Auth::guard($id === 'employee' ? 'employee' : null);

        $auth = Auth::guard($id === 'employee' ? null : 'employee');

        if (! $current->check() && ! $auth->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        if ($current->check() || $auth->check()) {
            return;
        }

        $guard = Filament::auth();

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentPanel();

        abort_if(
            $user instanceof FilamentUser ?
                (! $user->canAccessPanel($panel)) :
                (config('app.env') !== 'local'),
            403,
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        return $request->expectsJson() ? null : url('login');
    }
}
