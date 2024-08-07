<?php

namespace App\Jobs;

use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class DumpDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?User $user;

    private $time;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->user = auth()->user();

        $this->queue = 'main';

        $this->time = now();
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return date('Y-m-d-His');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Artisan::call('dump-database');

            Notification::make()
                ->title('Database dump successful')
                ->body('The database has been successfully dumped to disk initiated at ' . $this->time->format('Y-m-d H:i:s.'))
                ->sendToDatabase($this->user);
        } catch (Exception $exception) {
            Notification::make()
                ->title('Database dump failed')
                ->body('The database dump failed to complete initiated at ' . $this->time->format('Y-m-d H:i:s.'))
                ->sendToDatabase($this->user);
        }
    }
}
