<?php

namespace App\Helpers;

use App\Models\Timelog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GetRawAttendancePunch
{
    public function __invoke(Collection $timelogs, Carbon $date): array
    {
        return $this->extract($timelogs, $date);
    }

    public function extract(Collection $timelogs, Carbon $date)
    {
        $timelogs->ensure(Timelog::class);

        $timelogs = $timelogs->filter(fn ($timelog) => $timelog->time->isSameDay($date));

        $roster = [];

        foreach (['p1', 'p2', 'p3', 'p4'] as $state) {
            $timelists = $timelogs->reject(fn ($punch) => in_array($punch->id, array_column($roster, 'id')));

            $timelists = match ($state) {
                'p1', 'p3' => $timelists->reject(fn ($timelog) => in_array($timelog->id, array_column($roster, 'id')))->filter->in,
                'p2', 'p4' => $timelists->reject(fn ($timelog) => in_array($timelog->id, array_column($roster, 'id')))->filter->out,
                default => collect(),
            };

            $timelists = match ($state) {
                'p1' => $timelists->filter(fn ($punch) => $punch->time->lt($date->clone()->setTime(11, 0, 0))),
                'p2' => $timelists->filter(fn ($punch) => $punch->time->lt($date->clone()->setTime(13, 0, 0))),
                'p3' => $timelists->filter(fn ($punch) => $punch->time->gte($date->clone()->setTime(11, 0, 0))),
                'p4' => $timelists->filter(fn ($punch) => $punch->time->gte($date->clone()->setTime(13, 0, 0))),
            };

            $timelists = match ($state) {
                'p1', 'p2' => $timelists->sortBy(fn ($punch) => $punch->time->clone()->subYears((int) $punch->scanner->priority)),
                'p3', 'p4' => $timelists->sortByDesc(fn ($punch) => $punch->time->clone()->addYears((int) $punch->scanner->priority)),
            };

            $punched = $timelists->reject(fn ($timelog) => ($log = end($roster)) ? $timelog->time->gte($log['time']) : $log)->first();

            if (is_null($punched)) {
                continue;
            }

            $roster[$state] = [
                'id' => $punched->id,
                'time' => $punched->time->format('H:i:s'),
                'foreground' => $punched->scanner?->foreground_color,
                'background' => $punched->scanner?->background_color,
                'recast' => $punched->recast,
            ];
        }

        return $roster;
    }
}
