<?php

namespace App\Jobs;

use App\Models\Capture;
use App\Models\Scanner;
use App\Traits\RunsZkScript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;

class RunLiveCapture implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use RunsZkScript;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const SCRIPT = 'capture.py';

    public Capture $capture;

    public $deleteWhenMissingModels = true;

    public $timeout = 31536000;

    public $tries = 1;

    public function __construct(
        Scanner $scanner
    ) {
        $this->initialize();

        $this->scanner = $scanner;

        $this->capture = $scanner->capture?->fresh() ?? $scanner->capture()->create();

        $this->onQueue('capture');
    }

    public function handle(): void
    {
        $initiated = microtime(true);

        $process = Process::forever();

        try {
            $process = $process->start($this->command());

            $this->capture->update([
                'status' => 'capturing',
                'pid' => $process->id(),
                'command' => join(' ', $this->command()),
                'runtime' => 0,
                'result' => '',
                'job_id' => $this->job->getJobId(),
            ]);

            while ($process->running()) {
                $this->capture->fresh()->update(['runtime' => round(microtime(true) - $initiated)]);

                sleep(1);
            }
        } finally {
            if ($process->running()) {
                $process->signal(SIGKILL);
            }

            $this->capture->update([
                'status' => 'terminated',
                'result' => trim($process->latestOutput()),
                'terminate' => false,
            ]);
        }
    }

    public function uniqueId(): string
    {
        return $this->scanner->id;
    }
}
