<?php

namespace App\Listeners;

use App\Events\EmployeesImported;
use App\Events\TimelogsProcessed;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\Upload;
use App\Pipes\SortTimelogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Pipeline\Pipeline;

class AddUploadHistory implements ShouldQueue
{
    use Queueable;

    /**
     * Handle the event.
     */
    public function handle(TimelogsProcessed|EmployeesImported $event): void
    {
        $history = Upload::make();

        $history->forceFill([
            'time' => $event->time,
            'ip_address' => request()->ip(),
            'user_name' => $event->user?->username ?? '',
            'type' => match (get_class($event)) {
                TimelogsProcessed::class => Timelog::class,
                EmployeesImported::class => Employee::class,
                default => null,
            },
        ]);

        if ($event instanceof TimelogsProcessed) {
            $scanner = $event->scanner instanceof Scanner ? $event->scanner : Scanner::find($event->scanner);

            $sorted = app(Pipeline::class)
                ->send(is_array($event->data) ? collect($event->data) : $event->data)
                ->through([SortTimelogs::class])
                ->thenReturn();

            $history->forceFill([
                'scanner_name' => $scanner->name,
                'scanner_id' => $scanner->id,
                'data' => [
                    'earliest' => $sorted->first()['time']->format('Y-m-d H:i:s'),
                    'latest' => $sorted->last()['time']->format('Y-m-d H:i:s'),
                    'rows' => $sorted->count(),
                    'via' => $event->download ? 'File Upload' : 'Download',
                ],
            ]);
        } elseif ($event instanceof EmployeesImported) {
            $history->forceFill([
                'data' => [
                    'employees' => $event->data->count(),
                    'enrollments' => $event->data->flatMap(fn ($e) => $e['scanners'])->count(),
                    'offices' => $event->data->map->employee->countBy('office')->toArray(),
                ],
            ]);
        }

        $history->user()->associate($event->user);

        $history->save();
    }
}
