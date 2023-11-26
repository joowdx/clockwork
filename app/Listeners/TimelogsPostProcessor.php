<?php

namespace App\Listeners;

use App\Events\TimelogsProcessed;
use App\Models\Scanner;
use App\Pipes\SortTimelogs;
use Illuminate\Contracts\Pipeline\Pipeline;

class TimelogsPostProcessor
{
    /**
     * Handle the event.
     */
    public function handle(TimelogsProcessed $event): void
    {
        $scanner = $event->scanner instanceof Scanner ? $event->scanner : Scanner::find($event->scanner);

        if (empty($event->data)) {
            $scanner->timelogs()->official(false)->update(['hidden' => true]);

            return;
        }

        $latest = app(Pipeline::class)
            ->send(is_array($event->data) ? collect($event->data) : $event->data)
            ->through([SortTimelogs::class])
            ->thenReturn()
            ->last();

        $scanner->timelogs()->official(false)->where('time', '>=', $latest->time)->update(['hidden' => false]);
    }
}
