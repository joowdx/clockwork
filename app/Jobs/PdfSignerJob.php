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
        protected string $identifier,
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
    public function handle(): void
    {
        try {
            $employees = Employee::whereIn('uid', array_column($this->employees, 'uid'))->get();

            foreach ($this->employees as $row) {
                $employee = $employees->first(fn ($employee) => $employee->uid === $row['uid']);

                (new SignPdfAction) (
                    $employee,
                    $this->path,
                    null,
                    $row['field'],
                    $row['coordinates'],
                    $row['page'] ?? 1,
                    [
                        'reason' => @$row['reason'],
                        'location' => @$row['location'],
                        'yml' => @$row['yml'],
                    ],
                );
            }

            foreach ($this->signatures as $signature) {
                (new SignPdfAction) (
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
                        'yml' => @$row['yml'],
                    ],
                    false,
                    $signature['certificate'],
                    $signature['specimen'],
                    $signature['password'],
                );
            }

            Http::asMultipart()
                ->attach('file', file_get_contents($this->path), 'file.pdf')
                ->post($this->callback, [
                    [
                        'name' => 'identifier',
                        'contents' => $this->identifier,
                    ],
                    [
                        'name' => 'status',
                        'contents' => 'success',
                    ],
                    [
                        'name' => 'message',
                        'contents' => 'The PDF file has been signed successfully.',
                    ],
                ]);
        } catch (Throwable $exception) {

            $this->failed($exception);

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
