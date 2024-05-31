<?php

namespace App\Listeners;

use App\Events\TimelogsFlushed;
use App\Events\TimelogsSynchronized;
use App\Models\Activity;

class ActivityMonitor
{
    /**
     * Handle the event.
     */
    public function handle(
        TimelogsFlushed|TimelogsSynchronized $event
    ): void {
        $activity = Activity::make(['user_id' => $event->user?->id]);

        if (in_array($event::class, [TimelogsFlushed::class, TimelogsSynchronized::class])) {
            $activity->activitable()->associate($event->scanner);

            $activity->data = [
                'action' => $event::class === TimelogsFlushed::class ? 'flush' : $event->action,
                'records' => $event->records,
            ];

            if ($event::class === TimelogsSynchronized::class) {
                $activity->data = array_merge($activity->data, [
                    'month' => $event->month,
                    'latest' => $event->latest,
                    'earliest' => $event->earliest,
                ]);

                if ($event->action === 'import') {
                    $activity->data = array_merge($activity->data, [
                        'file' => $event->file,
                    ]);
                }

                if ($event->action === 'fetch') {
                    $activity->data = array_merge($activity->data, [
                        'host' => $event->credentials['host'],
                        'port' => $event->credentials['port'],
                        'pass' => $event->credentials['pass'],
                    ]);
                }
            }
        }

        $activity->save();
    }
}
