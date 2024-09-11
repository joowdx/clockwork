<?php

namespace App\Policies;

use App\Enums\UserPermission;
use App\Models\User;
use Filament\Facades\Filament;

class HolidayPolicy
{
    public function viewAny(?User $user): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user?->hasPermission(UserPermission::HOLIDAY),
            'secretary' => true,
            default => false,
        };
    }
}
