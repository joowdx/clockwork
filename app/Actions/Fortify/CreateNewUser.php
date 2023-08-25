<?php

namespace App\Actions\Fortify;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            'type' => ['required', Rule::in(collect(UserType::cases())->map->value)],
            'disabled' => ['nullable', 'boolean'],
            'offices' => ['nullable', 'string'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'username' => mb_strtolower($input['username']),
            'password' => Hash::make($input['password']),
            'title' => @$input['title'],
            'type' => $input['type'],
            'disabled' => (bool) @$input['disabled'],
            'offices' => str_getcsv(@$input['offices'] ?? ""),
        ]);
    }
}
