<?php

namespace App\Events;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimelogsFlushed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Scanner $scanner,
        public readonly User $user,
        public readonly int $records = 0,
    ) {

    }
}
