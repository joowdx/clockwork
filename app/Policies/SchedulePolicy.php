<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\User;
use Filament\Facades\Filament;

class SchedulePolicy
{
    public function viewAny(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'superuser', 'manager' => $user?->hasPermission(UserPermission::SCHEDULE) ?? false,
            'secretary' => settings('schedule'),
            default => false,
        };
    }
}
