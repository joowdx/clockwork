<?php

namespace App\Repositories\SQLite;

use App\Contracts\BackupRepository;
use App\Contracts\BaseRepository;
use App\Models\Employee;
use App\Models\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository implements BackupRepository
{
    public function backUp(Model|Collection $model): void
    {
        switch(get_class($model)) {
            case Employee::class: {
                $this->model()->firstOrCreate($this->transformData($model->toArray()));

                return;
            }
            case Collection::class: {
                $this->upsert(
                    $model->toArray(),
                    ['employee_id'],
                );

                return;
            }
        }
    }

    public function sync(Authenticatable $user): void
    {
        $this->clear($user);

        $this->backUp($user->employees()->active()->get());
    }

    public function clear(Authenticatable $user): void
    {
        $this->model()->whereUserId($user->id)->temporary()->delete();
    }

    protected function transformData(array $payload): array
    {
        return [
            'employee_id' => $payload['employee_id'] ?? $payload['id'],
        ];
    }
}
