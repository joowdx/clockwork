<?php

namespace App\Jobs;

use App\Actions\SignPdfAction;
use App\Models\Employee;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class PdfSignerJob implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $path,
        protected string $callback,
        protected array $employees,
        protected array $signatures,
    ) {
        $this->queue = 'main';
    }

    /**
     * Execute the job.
     */
    public function handle(SignPdfAction $signer): void
    {
        foreach ($this->employees as $row) {
            $employee = Employee::where('uid', $row['uid'])->first();

            $signer(
                $employee,
                $this->path,
                null,
                $employee->uid,
                $row['coordinates'],
                $row['page'] ?? 1,
                [
                    'reason' => @$row['reason'],
                    'location' => @$row['location'],
                ],
            );
        }

        foreach ($this->signatures as $signature) {
            $signer(
                null,
                $this->path,
                null,
                $signature['field'],
                $signature['coordinates'],
                $signature['page'] ?? 1,
                [
                    'reason' => @$row['reason'],
                    'location' => @$row['location'],
                    'contact' => @$row['contact'],
                ],
                false,
                $signature['certificate'],
                $signature['specimen'],
                $signature['password'],
            );
        }

        try {
            Http::attach('file', file_get_contents($this->path), 'file.pdf')
                ->post($this->callback, [
                    'status' => 'success',
                    'message' => 'The PDF has been signed successfully.',
                ]);
        } finally {
            if (file_exists($this->path)) {
                unlink($this->path);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Http::post($this->callback, [
            'status' => 'failed',
            'message' => $exception->getMessage(),
        ]);
    }
}
