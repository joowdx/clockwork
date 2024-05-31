<?php

namespace App\Filament\Auth;

use Illuminate\Contracts\Support\Htmlable;

class Login extends \Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.auth.login';

    public function getSubheading(): string | Htmlable | null
    {
        return config('app.name');
    }
}
