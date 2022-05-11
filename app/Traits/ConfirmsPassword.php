<?php

namespace App\Traits;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

trait ConfirmsPassword
{
    public function confirmPassword(?string $password)
    {
        $confirmed = app(ConfirmPassword::class)(
            app(StatefulGuard::class), auth()->user(), $password
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }
    }

}
