<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user?->hasPermission(UserPermission::USER) ?? false;
    }
}
