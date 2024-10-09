<?php

namespace App\Jobs;

use App\Actions\ExportTimesheet;
use App\Models\Export;
use App\Models\User;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;

class ExportTimesheets implements ShouldQueue
{
    use Queueable;

    private User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ExportTimesheet $exporter,
    ) {
        $this->user = Auth::user();

        $this->queue = 'main';
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->exporter->id();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            ['filename' => $filename, 'content' => $content] = $this->exporter->download(false);

            $export = Export::create([
                'filename' => $filename,
                'content' => $content,
                'user_id' => $this->user->id,
            ]);

            $body = <<<HTML
                <b>$filename</b> <br>
                Your export is ready for download. This will only be available for 24 hours.
            HTML;

            $notification = Notification::make()
                ->icon('heroicon-o-archive-box-arrow-down')
                ->title('Timesheet Export Ready')
                ->body(str($body)->toHtmlString())
                ->actions([
                    Action::make('download')
                        ->button()
                        ->color('primary')
                        ->markAsRead()
                        ->url(route('export', $export), true),
                ]);
        } catch (Exception $exception) {
            $notification = Notification::make()
                ->danger()
                ->title('Timesheet Export Failed')
                ->body('Something went wrong. Please try again.');
        }

        $notification->sendToDatabase($this->user);

        $notification->broadcast($this->user);

        if ($exception ?? false) {
            throw $exception;
        }
    }
}
