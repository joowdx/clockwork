<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class TimelogsProcessed
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
        public Collection|LazyCollection|array $data,
    ) {
        $this->time = now();
    }
}
