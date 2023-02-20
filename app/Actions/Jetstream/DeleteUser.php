<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function delete($user)
    {
        abort_if($user->isAdministrator() && User::admin()->count() <= 1, 403, 'Must have at least one administrator account left.');

        $user->deleteProfilePhoto();

        $user->delete();
    }
}
