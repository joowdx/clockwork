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
            'superuser', 'secretary' => $user->hasPermission(SchedulePermission::VIEW_ALL),
            default => false,
        };
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::VIEW),
            'secretary' => in_array($schedule->office_id, $user->offices->pluck('id')->toArray()) && $user->hasPermission(SchedulePermission::VIEW),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser', 'secretary' => $user->hasPermission(SchedulePermission::CREATE),
            default => false,
        };;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::UPDATE),
            'secretary' => in_array($schedule->office_id, $user->offices->pluck('id')->toArray()) && $user->hasPermission(SchedulePermission::UPDATE),
            default => false,
        };
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::DELETE),
            'secretary' => in_array($schedule->office_id, $user->offices->pluck('id')->toArray()) && $user->hasPermission(SchedulePermission::DELETE),
            default => false,
        };
    }

    public function deleteAny(User $user): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser', 'secretary' => $user->hasPermission(SchedulePermission::BATCH_DELETE),
            default => false,
        };
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::RESTORE),
            'secretary' => in_array($schedule->office_id, $user->offices->pluck('id')->toArray()) && $user->hasPermission(SchedulePermission::RESTORE),
            default => false,
        };
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser' => $user->hasPermission(SchedulePermission::BATCH_RESTORE),
            'secretary' => in_array($schedule->office_id, $user->offices->pluck('id')->toArray()) && $user->hasPermission(SchedulePermission::BATCH_RESTORE),
            default => false,
        };
    }

    public function forceDeleteAny(User $user): bool
    {
        return match (Filament::getCurrentPanel()->getId()) {
            'superuser', 'secretary' => $user->hasPermission(SchedulePermission::BATCH_FORCE_DELETE),
            default => false,
        };
    }
}
