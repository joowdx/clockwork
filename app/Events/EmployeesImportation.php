<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeesImportation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $username;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private User $user,
        public string $status,
        public string $message,
        public string $time,
        public string $duration,
    ) {
        $this->username = $user->username;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn(): array|Channel
    {
        return [
            new PrivateChannel("users.{$this->user->id}"),
            new PrivateChannel('administrators')
        ];
    }
}
