<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;

class RequestPolicy
{
    public function viewAny(User|Employee|null $user)
    {
        if ($user === null) {
            return false;
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user?->hasPermission(UserPermission::REQUEST),
            'director', 'leader', 'manager', 'secretary' => true,
            default => false,
        };
    }
}
