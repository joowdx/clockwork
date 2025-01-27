<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\UserPermission;
use App\Models\Schedule;
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
            'superuser' => $user?->hasPermission(UserPermission::SCHEDULE),
            'manager', 'secretary' => settings('schedule') ?? false,
            default => false,
        };
    }

    public function view(?User $user, Schedule $schedule): bool
    {
        if ($user === null) {
            return false;
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user?->hasPermission(UserPermission::SCHEDULE),
            'manager', 'secretary' => ! in_array($schedule?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN]),
            default => false,
        };
    }

    public function update(?User $user, Schedule $schedule): bool
    {
        if ($user === null) {
            return false;
        }

        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user?->hasPermission(UserPermission::SCHEDULE),
            'manager', 'secretary' => in_array($schedule?->request?->status, [null, RequestStatus::CANCEL, RequestStatus::RETURN]),
            default => false,
        };
    }
}
