<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeesImported
{
    use Dispatchable;

    public Carbon $time;

    /**
     * Create a new event instance.
     *
     * @param  Illuminate\Http\UploadedFile  $file
     * @return void
     */
    public function __construct(
        public User $user,
        public Collection|array $data,
    ) {
        $this->time = now();
    }
}
