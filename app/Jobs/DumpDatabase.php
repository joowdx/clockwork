<?php

namespace App\Jobs;

use App\Actions\DumpDatabase as DumpDatabaseAction;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
    public function handle(DumpDatabaseAction $dumper): void
    {
        try {
            $dump = $dumper();

            Notification::make()
                ->title('Database dump successful')
                ->body('The database has been successfully dumped to disk at ' . $dump->created_at)
                ->sendToDatabase($this->user);
        } catch (Exception $exception) {
            Notification::make()
                ->title('Database dump failed')
                ->body($exception->getMessage())
                ->sendToDatabase($this->user);
        }
    }
}
