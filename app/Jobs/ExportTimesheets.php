<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\User;
use App\Services\TimesheetExporter;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExportTimesheets implements ShouldQueue
{
    use Queueable;

    private User $user;

    private Export $export;

    private bool $exists = false;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private TimesheetExporter $exporter,
    ) {
        $this->user = Auth::user();

        $this->queue = 'main';

        if (Export::where('details->hash', $this->exporter->id())->exists()) {
            $this->exists = true;

            $this->export = Export::where('details->hash', $this->exporter->id())->first();

            return;
        }

        $export = Export::make()
            ->forceFill([
                'filename' => '',
                'user_id' => $this->user->id,
                'details' => [
                    'hash' => $this->exporter->id(),
                ],
            ]);

        $export->save();

        $this->export = $export;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->export->details->hash;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->exists && ! $this->exporter->skipChecks()) {
                $this->export->forceFill(['created_at' => now()])->save();
            } else {
                ['filename' => $filename, 'content' => $content] = $this->exporter->download(false);

                $this->export->update([
                    'filename' => $filename,
                    'content' => $content,
                ]);
            }

            $body = <<<HTML
                <b>{$this->export->filename}</b> <br>
                Your export is ready for download. This will only be available for 1 hour.
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
                        ->url(route('export', $this->export), true),
                ]);
        } catch (Exception $exception) {
            $notification = Notification::make()
                ->danger()
                ->title('Timesheet Export Failed')
                ->body('Something went wrong. Please try again.');

            Log::error('Timesheet export failed', [
                'user' => $this->user->id,
                'exception' => $exception->getMessage(),
                'exporter' => $this->exporter->id(),
            ]);

            $this->export->delete();
        }

        $notification->sendToDatabase($this->user);

        $notification->broadcast($this->user);
    }
}
