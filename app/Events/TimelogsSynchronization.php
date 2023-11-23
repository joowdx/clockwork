<?php

namespace App\Events;

use App\Models\Scanner;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimelogsSynchronization implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $id;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private Scanner $scanner,
        public string $status,
        public string $message,
        public string $user,
        public string $time,
        public string $duration,
    ) {
        $this->id = $scanner->id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("scanners.{$this->scanner->id}");
    }
}
