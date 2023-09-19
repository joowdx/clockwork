<?php

namespace App\Listeners;

use App\Events\EmployeesImported;
use App\Events\TimelogsProcessed;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\Upload;

class AddUploadHistory
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TimelogsProcessed|EmployeesImported $event): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $user = $event->user;

        $scanner = Scanner::find(request()->scanner_id);

        $history = Upload::make();

        $history->forceFill([
            'time' => now(),
            'ip' => request()->ip(),
            'user_name' => $user?->username ?? '',
            'scanner_name' => $scanner->name,
            'type' => match (get_class($event)) {
                TimelogsProcessed::class => Timelog::class,
                EmployeesImported::class => Employee::class,
                default => null,
            },
        ]);

        if ($event instanceof TimelogsProcessed) {
            $history->forceFill([
                'data' => [
                    'earliest' => '',
                    'latest' => '',
                    'rows' => '',
                ],
            ]);
        } elseif ($event instanceof EmployeesImported) {
            $history->forceFill([
                'data' => [
                    'earliest' => '',
                    'latest' => '',
                    'rows' => '',
                ],
            ]);
        }

        $history->user()->associate($user);

        $history->save();
    }
}
