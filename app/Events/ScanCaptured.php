<?php

namespace App\Events;

use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Timelog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScanCaptured implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private string $ip_address,
        private string $data
    ) {
        $data = json_decode($data);

        try {
            $scanner = Scanner::whereIpAddress($ip_address)->firstOrFail();

            Employee::whereHas('scanners', fn ($q) => $q->whereUid($data->uid))->firstOrFail();

            Timelog::make([...(array) $data, 'scanner_id' => $scanner->id])->forceFill(['uid' => $data->uid])->save();
        } catch (ModelNotFoundException) {
            return;
        }
    }
}
