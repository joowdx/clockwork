<?php

namespace App\Listeners;

use App\Events\EmployeesImported;
use App\Events\TimelogsProcessed;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\Upload;
use App\Pipes\SortTimelogs;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class AddUploadHistory
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private Request $request
    ) {

    }

    /**
     * Handle the event.
     */
    public function handle(TimelogsProcessed|EmployeesImported $event): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $user = $this->request->user();

        $history = Upload::make();

        $history->forceFill([
            'time' => now(),
            'ip_address' => request()->ip(),
            'user_name' => $user?->username ?? '',
            'type' => match (get_class($event)) {
                TimelogsProcessed::class => Timelog::class,
                EmployeesImported::class => Employee::class,
                default => null,
            },
        ]);

        if ($event instanceof TimelogsProcessed) {
            $scanner = Scanner::find($this->request->scanner);

            $sorted = app(Pipeline::class)
                ->send($event->data)
                ->through([SortTimelogs::class])
                ->thenReturn();

            $history->forceFill([
                'scanner_name' => $scanner->name,
                'data' => [
                    'earliest' => $sorted->first()['time']->format('Y-m-d H:i:s'),
                    'latest' => $sorted->first()['time']->format('Y-m-d H:i:s'),
                    'rows' => $sorted->count(),
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

        $history->user()->associate($user);

        $history->save();
    }
}
