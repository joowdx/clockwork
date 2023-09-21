<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Model;
use App\Models\Scanner;
use App\Models\User;

class AssignmentService
{
    public function attach(User|Scanner $model, array|string $payload): void
    {
        $this->detach($model, $payload);

        $this->relationship($model, $payload, 'attach');
    }

    public function detach(User|Scanner $model, array|string $payload): void
    {
        $this->relationship($model, $payload, 'detach');
    }

    public function sync(User|Scanner $model, array|string $payload): void
    {
        $this->relationship($model, $payload, 'sync');
    }

    public function destroy(Assignment $assignment): void
    {
        $assignment->delete();
    }

    protected function relationship(User|Scanner $model, array|string $payload, string $action)
    {
        switch (get_class($model)) {
            case User::class:
                $model->scanners()->{$action}($payload);
                break;

            case Scanner::class:
                $model->users()->{$action}($payload);
                break;
        }
    }
}
