<?php

namespace App\Policies;

use App\Enums\Permissions\SchedulePermission;
use App\Models\Schedule;
use App\Models\User;
use Filament\Facades\Filament;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => true,
            'secretary' => true,
            default => false,
        };
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::VIEW),
            'secretary' => $schedule->request()->exists(),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::UPDATE),
            'secretary' => $schedule->request()->doesntExist() || ($schedule->requestable && $schedule->request_requestable),
            default => false,
        };
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return true;
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return true;
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return true;
    }
}
