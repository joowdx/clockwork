<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  mixed  $user
     * @return void
     */
    public function update($user, array $input)
    {
        Validator::make($input, [
            'username' => ['required', 'string', 'unique:users,username,'.$user->id, 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(collect(UserRole::cases())->map->value)],
            'disabled' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'offices' => ['nullable', 'string'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        $user->forceFill([
            'username' => $input['username'],
            'name' => $input['name'],
            'title' => $input['title'],
            'role' => $input['role'],
            'disabled' => (bool) @$input['disabled'],
            'offices' => collect(str_getcsv(@$input['offices'] ?? ''))->map(fn ($o) => trim($o))->toArray(),
        ])->save();

        if ($input['role'] == UserRole::SYSTEM->value) {
            $user->employee()->dissociate()->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  mixed  $user
     * @return void
     */
    protected function updateVerifiedUser($user, array $input)
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
