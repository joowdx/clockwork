<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\User;
use Filament\Facades\Filament;

class HolidayPolicy
{
    public function viewAny(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'superuser', 'manager' => $user?->hasPermission(UserPermission::HOLIDAY) ?? false,
            'secretary' => true,
            default => false,
        };
    }
}
