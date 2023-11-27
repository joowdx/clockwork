<?php

namespace App\Jobs;

use App\Events\TimelogsSynchronization;
use App\Events\TimelogsProcessed;
use App\Models\Scanner;
use App\Models\User;
use App\Services\DownloaderService;
use App\Services\TimelogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SynchronizeTimelogs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private DownloaderService $downloader;

    public $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Scanner $scanner,
        private User $user,
        private Carbon $time,
    ) {
        $this->downloader = new DownloaderService($scanner);

        $this->onQueue('process');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->scanner->id;
    }

    /**
     * Execute the job.
     */
    public function handle(TimelogService $service): void
    {
        if (is_null($this->scanner->ip_address) || empty($this->scanner->ip_address)) {
            throw new RuntimeException('Scanner is not properly configured.');
        }

        DB::transaction(function () use ($service) {
            $data = $this->downloader->getPreformattedAttendance();

            $service->insert($this->scanner, $data);

            $message = "Timelogs have been succesfully synchronized from {$this->scanner->name} at '{$this->scanner->ip_address}' with " . count($data) . " records.";

            TimelogsProcessed::dispatch($this->user, $data, $this->scanner);

            TimelogsSynchronization::dispatch(
                $this->scanner,
                "success",
                $message,
                $this->user->username,
                $this->time,
                now()->diffInSeconds($this->time),
            );
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        TimelogsSynchronization::dispatch(
            $this->scanner,
            "error",
            "Sync error | {$this->scanner->name}: " . trim($exception->getMessage()),
            $this->user->username,
            $this->time,
            now()->diffInSeconds($this->time),
        );
    }
}
