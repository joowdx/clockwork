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
     * @param  \App\Models\User  $user
     * @return void|bool
     */
    public function before(User $user)
    {
        $user->isAdministrator();
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Scanner $scanner)
    {
        return $scanner->createdBy?->is($user) ?? $scanner->users->contains($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Scanner $scanner)
    {
        return $scanner->createdBy?->is($user) ?? $scanner->users->contains($user);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Scanner  $scanner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Scanner $scanner)
    {
        return $scanner->createdBy?->is($user);
    }
}
