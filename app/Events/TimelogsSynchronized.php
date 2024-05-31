<?php

namespace App\Events;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimelogsSynchronized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Scanner $scanner,
        public readonly User $user,
        public readonly string $action,
        public readonly ?string $month = '',
        public readonly ?string $earliest = '',
        public readonly ?string $latest = '',
        public readonly ?int $records = 0,
        public readonly ?string $file = '',
        public readonly ?array $credentials = [],
    ) {

    }
}
