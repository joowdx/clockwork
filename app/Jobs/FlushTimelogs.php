<?php

namespace App\Jobs;

use App\Events\TimelogsFlushed;
use App\Models\Scanner;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class FlushTimelogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Scanner $scanner,
    ) {
        $this->user = auth()->user();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->scanner->timelogs()->where('pseudo', 1)->update(['shadow' => 1]);

            $count = $this->scanner->timelogs()->where('pseudo', 0)->delete();

            TimelogsFlushed::dispatch($this->scanner, $this->user, $count);

            Notification::make()
                ->warning()
                ->title('Scanner timelogs are flushed')
                ->sendToDatabase($this->user);
        });
    }
}
