<?php

namespace App\Repositories\SQLite;

use App\Contracts\BackupRepository;
use App\Models\Model;
use App\Models\TimeLog;
use App\Repositories\TimeLogRepository as Repository;
use App\Services\TimeLogService as Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class TimeLogRepository extends Repository implements BackupRepository
{
    public function backUp(Model|Collection $model): void
    {
        switch(get_class($model)) {
            case TimeLog::class: {
                $this->query()->firstOrCreate([
                    ...$this->transformData($model->toArray()),
                    'employee_id' => $model->employee->id,
                    'time_log_id' => $model->id,
                ], [
                    'id' => str()->orderedUuid(),
                ]);

                return;
            }
            case Collection::class: {

                $model->reject->backedUp->filter->proper->each(fn ($t) => $this->backUp($t));

                $model->reject->backedUp->reject->proper->reject->acceptable->each(fn ($t) => $this->backUp($t));

                $model->reject->backedUp->reject->proper->filter->acceptable->each(fn ($t) => $this->backUp(app(Service::class)->accept($t)));

                return;
            }
        }
    }

    public function sync(Authenticatable $user): void
    {
        $this->clear($user);

        $this->backUp($user->employees->filter->backedUp->flatMap->mainLogs);
    }

    public function clear(Authenticatable $user): void
    {
        $this->query()->whereUserId($user->id)->temporary()->delete();
    }
}
