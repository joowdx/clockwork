<?php

namespace App\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\SerializesModels;

class EmployeesImported
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Illuminate\Http\UploadedFile  $file
     * @return void
     */
    public function __construct(
        public Authenticatable $user,
        public UploadedFile $file,
    ) {
    }
}
