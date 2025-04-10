<?php

namespace App\Traits;

use App\Models\Holiday;
use App\Models\Timelog;
use App\Models\Timesheet;
use App\Models\Timetable;
use Illuminate\Support\Collection;

trait TimelogsHasher
{
    public function generateDigest(
        Timesheet|Timetable|null $model = null,
        ?Collection $timelogs = null,
        ?Collection $holidays = null,
        bool $check = true
    ): string {
        $model = $model ?? $this;

        $timelogs = $timelogs ?? $model->timelogs;

        $scanners = $timelogs->loadMissing('scanner')->map(fn ($timelog) => ['uid' => $timelog->uid, ...$timelog->scanner?->only('print', 'active')]);

        $timelogs = $timelogs->when($check, fn ($timelogs) => $timelogs->ensure(Timelog::class))->map->withoutRelations();

        $holidays = $holidays ?? (
            $model instanceof Timesheet
                ? Holiday::whereMonth('date', explode('-', $model->month)[1])->whereYear('date', explode('-', $model->month)[0])->get()
                : Holiday::search($model->date)
        );

        return hash('sha512', json_encode([
            'id' => $model->id,
            'timelogs' => $timelogs,
            'scanners' => $scanners,
            'holidays' => $holidays,
        ]));
    }

    public function checkDigest(
        Timesheet|Timetable|null $model = null,
        ?Collection $timelogs = null,
        ?Collection $holidays = null
    ): bool {
        $model = $model ?? $this;

        $timelogs = $timelogs ?? $model->timelogs;

        $timelogs->ensure(Timelog::class);

        return $model->digest === $this->generateDigest($model, $timelogs, $holidays, false);
    }
}
