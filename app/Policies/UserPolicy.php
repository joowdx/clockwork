<?php

namespace App\Policies;

use App\Enums\Permissions\UserPermission;
use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function view(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::VIEW);
    }

    public function viewAny(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::VIEW_ALL);
    }

    public function create(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::CREATE);
    }

    public function update(?User $user, User $target): bool
    {
        return $user?->hasPermission(UserPermission::UPDATE) && ! $target->hasRole(UserRole::ROOT);
    }

    public function delete(?User $user, User $target): bool
    {
        return $user?->hasAnyPermission(UserPermission::BATCH_DELETE, UserPermission::DELETE) && ! $target->hasRole(UserRole::ROOT);
    }

    public function deleteAny(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::BATCH_DELETE);
    }

    public function restore(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::BATCH_RESTORE, UserPermission::RESTORE);
    }

    public function restoreAny(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::BATCH_RESTORE);
    }

    public function forceDelete(?User $user): bool
    {
        return $user?->hasAnyPermission(UserPermission::BATCH_FORCE_DELETE, UserPermission::FORCE_DELETE);
    }

    public function forceDeleteAny(?User $user): bool
    {
        return $user?->hasPermission(UserPermission::BATCH_FORCE_DELETE);
    }
}
