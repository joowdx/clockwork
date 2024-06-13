<?php

namespace App\Jobs;

use App\Events\TimelogsSynchronized;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\User;
use App\Services\TimelogFetcher;
use App\Services\TimelogsFetcher\TimelogsFetcherException;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FetchTimelogs implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly User $user;

    public readonly Scanner $scanner;

    private readonly TimelogFetcher $fetcher;

    private readonly ?string $from;

    private readonly ?string $to;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $device,
        private readonly ?string $month = null,
        private readonly int $chunkSize = 1000,
        private readonly bool $notify = true,
    ) {
        $this->scanner = Scanner::where('uid', $device)->firstOrFail();

        $this->fetcher = new TimelogFetcher($this->scanner);

        $this->user = auth()->user();

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
        try {
            if (empty($this->scanner->host)) {
                throw new TimelogsFetcherException('Device connection not configured.');
            }

            $timelogs = $this->fetcher->fetchTimelogs($this->from, $this->to)->map(fn ($entry) => [
                'device' => $this->device,
                'uid' => $entry['uid'],
                'time' => $entry['time'],
                'state' => $entry['state'],
                'mode' => $entry['mode'],
            ]);

            DB::transaction(function () use ($timelogs) {
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
            });

            TimelogsSynchronized::dispatch(
                $this->scanner,
                $this->user,
                'fetch',
                $this->month,
                $timelogs->first()['time'] ?? null,
                $timelogs->last()['time'] ?? null,
                $timelogs->count(),
                null,
                [
                    'host' => $this->scanner->host,
                    'port' => $this->scanner->port,
                    'pass' => $this->scanner->pass,
                ]
            );

            if ($this->notify) {
                Notification::make()
                    ->success()
                    ->title('Fetch successful')
                    ->body(
                        str(<<<HTML
                            Timelogs of <i>{$this->scanner->name}</i> has been successfully fetched from the device <br>
                            <i>You may have to wait for a bit before the employees' records are updated</i>
                        HTML)
                            ->toHtmlString()
                    )
                    ->sendToDatabase($this->user);
            }
        } catch (TimelogsFetcherException $exception) {
            Notification::make()
                ->danger()
                ->title('Fetch failed')
                ->body(str("Errors occurred <i>{$this->scanner->name}</i>: <br> ".$exception->getMessage())->toHtmlString())
                ->sendToDatabase($this->user);
        }
    }
}
