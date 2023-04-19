<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'title' => ['nullable', 'string', 'max:255'],
            'administrator' => ['nullable', 'boolean'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'password' => Hash::make($input['password']),
            'title' => @$input['title'],
            'administrator' => @$input['administrator'],
        ]);
    }
}
