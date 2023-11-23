<?php

namespace App\Jobs;

use App\Events\TimelogsImportation;
use App\Events\TimelogsProcessed;
use App\Models\Scanner;
use App\Models\User;
use App\Services\TimelogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportTimelogs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Scanner $scanner,
        private string $file,
        private string $name,
        private User $user,
        private Carbon $time,
    ) {
        $this->onQueue('process');
    }

    /**
     * Execute the job.
     */
    public function handle(TimelogService $service): void
    {
        $data = null;

        DB::transaction(function () use (&$data, $service) {
            $data = $service->parse($this->scanner, $this->file);
        });

        $message = "Timelogs have been succesfully imported to {$this->scanner->name} from '{$this->name}' with " . count($data ?? []) . " records.";

        TimelogsImportation::dispatch(
            $this->scanner,
            "success",
            $message,
            $this->user->username,
            $this->time,
            now()->diffInSeconds($this->time),
        );

        TimelogsProcessed::dispatch($this->user, $data?->toArray(), $this->scanner);

        File::delete($this->file);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->scanner->id;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        File::delete($this->file);

        TimelogsImportation::dispatch(
            $this->scanner,
            "error",
            trim($exception->getMessage()),
            $this->user->username,
            $this->time,
            now()->diffInSeconds($this->time),
        );
    }
}
