<?php

namespace App\Policies;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScannerPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @return void|bool
     */
    public function before(User $user)
    {
        return $user->administrator ?: null;
    }

    /**
     * Determine whether the user can create a model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {

    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Scanner $scanner)
    {
        return $scanner->shared ?: $scanner->users->contains($user) ?: $scanner->users->isEmpty();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Scanner $scanner)
    {

    }
}
