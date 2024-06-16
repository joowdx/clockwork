<?php

namespace App\Jobs;

use App\Events\TimelogsSynchronized;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;
use League\Csv\Reader;

class ImportTimelogs implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly User $user;

    public readonly Scanner $scanner;

    private readonly ?string $from;

    private readonly ?string $to;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $device,
        private readonly string $filePath,
        private readonly string $fileName,
        private readonly ?string $month = null,
        private readonly int $chunkSize = 10000,
        private readonly bool $notify = true,
    ) {
        $this->user = auth()->user();

        $this->scanner = Scanner::where('uid', $device)->firstOrFail();

        $this->queue = 'main';

        if ($month) {
            $this->from = Carbon::parse($month)
                ->startOfMonth()
                ->subDay()
                ->format('Y-m-d H:i:s');

            $this->to = Carbon::parse($month)
                ->endOfMonth()
                ->addDay()
                ->format('Y-m-d H:i:s');
        } else {
            $this->from = null;

            $this->to = null;
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->device;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invalidDataException = new class extends Exception
        {
            public function __construct(
                public readonly ?string $title = null,
                public readonly ?string $body = null
            ) {
                parent::__construct();
            }
        };

        try {
            if (is_file($this->filePath) === false) {
                throw new $invalidDataException('Invalid file', 'The file uploaded is not recognized as a valid file.');
            }

            $csvReader = Reader::createFromPath($this->filePath)
                ->addFormatter(fn ($row) => array_map('trim', $row))
                ->setDelimiter("\t");

            if (isset($this->from) && isset($this->to)) {
                $csvReader = $csvReader->filter(fn ($row) => isset($this->month) ? $this->from <= $row[1] && $row[1] <= $this->to : false);
            }

            $timelogs = LazyCollection::make(fn () => yield from $csvReader->getRecords())->unique();

            $timelogs = $timelogs->map(function ($entry) use ($invalidDataException) {
                if (count($entry) !== 6) {
                    throw new $invalidDataException('Invalid entry found', 'Malformed: File uploaded may either be invalid or tampered');
                }

                if (! is_numeric($entry[2]) || $this->device !== (int) $entry[2]) {
                    throw new $invalidDataException('Invalid entry found', 'Device: File has a likelihood of being tampered with');
                }

                if (! is_numeric($entry[3])) {
                    throw new $invalidDataException('Invalid entry found', 'State: File has a likelihood of being tampered with');
                }

                if (! is_numeric($entry[4])) {
                    throw new $invalidDataException('Invalid entry found', 'Mode: File has a likelihood of being tampered with');
                }

                if (date('Y-m-d H:i:s', strtotime($entry[1])) != $entry[1]) {
                    throw new $invalidDataException('Invalid entry found', 'Time: File has a likelihood of being tampered with');
                }

                return [
                    'device' => $entry[2],
                    'uid' => $entry[0],
                    'time' => $entry[1],
                    'state' => $entry[3],
                    'mode' => $entry[4],
                ];
            });

            $timelogs->chunk($this->chunkSize)->each(function ($entries) {
                Timelog::upsert($entries->toArray(), [
                    'device',
                    'uid',
                    'time',
                    'state',
                    'mode',
                ], [
                    'uid',
                    'time',
                    'state',
                    'mode',
                ]);
            });

            TimelogsSynchronized::dispatch(
                $this->scanner,
                $this->user,
                'import',
                $this->month,
                $timelogs->first()['time'] ?? null,
                $timelogs->last()['time'] ?? null,
                $timelogs->count(),
                $this->fileName,
            );

            if ($this->notify) {
                Notification::make()
                    ->success()
                    ->title('Import successful')
                    ->body(
                        str(<<<HTML
                            Timelogs for {$this->scanner->name} device {$this->device} for the month of {$this->month} has been successfully imported from {$this->fileName} <br>
                            <i>You may have to wait for a bit before the employees' records are updated</i>
                        HTML)
                            ->toHtmlString()
                    )
                    ->sendToDatabase($this->user);
            }
        } catch (Exception $exception) {
            if ($exception instanceof $invalidDataException) {
                $title = <<<HTML
                    Scanner import {$this->scanner->name}($this->device) <br>
                    {$exception->title}
                HTML;

                $body = <<<HTML
                    {$exception->body} <br>
                    <i>{$this->fileName}</i>
                HTML;

                Notification::make()
                    ->danger()
                    ->title(str($title)->toHtmlString())
                    ->body(str($body)->toHtmlString())
                    ->sendToDatabase($this->user);

                return;
            }

            throw $exception;
        }
    }
}
