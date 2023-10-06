<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array
     */
    protected function passwordRules($user)
    {
        return match($user->developer) {
            true => [
                'required',
                'string',
                'confirmed',
                new Password,
            ],
            default => [
                'required',
                'string',
                'confirmed',
                (new Password())->requireUppercase()->requireNumeric()->requireSpecialCharacter(),
            ],
        };
    }
}
