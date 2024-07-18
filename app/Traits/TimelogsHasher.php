<?php

namespace App\Traits;

use App\Models\Timelog;
use App\Models\Timesheet;
use App\Models\Timetable;
use Illuminate\Support\Collection;

trait TimelogsHasher
{
    public function generateDigest(Timesheet|Timetable|null $model = null, ?Collection $timelogs = null, bool $check = true): string
    {
        $model = $model ?? $this;

        $timelogs = $timelogs ?? $model->timelogs;

        $scanners = $timelogs->loadMissing('scanner')->map(fn ($timelog) => ['uid' => $timelog->uid, ...$timelog->scanner->only('print', 'active')]);

        $timelogs = $timelogs->when($check, fn ($timelogs) => $timelogs->ensure(Timelog::class))->map->withoutRelations();

        return hash('sha512', json_encode(['id' => $model->id, 'timelogs' => $timelogs, 'scanners' => $scanners]));
    }

    public function checkDigest(Timesheet|Timetable|null $model = null, ?Collection $timelogs = null): bool
    {
        $model = $model ?? $this;

        $timelogs = $timelogs ?? $model->timelogs;

        $timelogs->ensure(Timelog::class);

        return $model->digest === $this->generateDigest($model, $timelogs, false);
    }
}
