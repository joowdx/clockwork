<?php

namespace App\Jobs;

use App\Actions\UpsertTimelogs;
use App\Events\TimelogsSynchronized;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\User;
use App\Services\TimelogFetcher;
use App\Services\TimelogsFetcher\TimelogsFetcherException;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FetchTimelogs implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 300;

    public readonly User|string $user;

    public readonly Scanner $scanner;

    private readonly TimelogFetcher $fetcher;

    private readonly ?string $from;

    private readonly ?string $to;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string|int $host,
        private readonly ?string $month = null,
        private readonly ?int $port = null,
        private readonly ?string $pass = null,
        private readonly ?string $token = null,
        private readonly ?string $callback = null,
        private readonly int $chunkSize = 1000,
        private readonly bool $notify = true,
        string $user = '',
    ) {
        if (is_numeric($host)) {
            $this->scanner = Scanner::where('uid', $host)->firstOrFail();

            $this->user = Auth::user();
        } else {
            $this->scanner = new Scanner([
                'host' => $host,
                'port' => $port,
                'pass' => $pass,
            ]);

            $this->user = $user;
        }

        $this->fetcher = new TimelogFetcher($this->scanner);

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

        $this->queue = 'main';
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->host ?? $this->scanner->host;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        match ((bool) $this->callback) {
            true => $this->remote(),
            default => $this->local(),
        };
    }

    protected function remote(): void
    {
        try {
            $timelogs = $this->fetcher->fetchTimelogs($this->from, $this->to)->map(fn ($entry) => [
                'uid' => $entry['uid'],
                'time' => $entry['time'],
                'state' => $entry['state'],
                'mode' => $entry['mode'],
            ]);

            Http::withToken($this->token)
                ->post($this->callback, [
                    'status' => 'success',
                    'message' => 'Timelogs fetched successfully',
                    'user' => $this->user,
                    'data' => json_encode([
                        'timelogs' => $timelogs,
                        'host' => $this->host,
                        'month' => $this->month,
                    ]),
                ]);
        } catch (Exception $exception) {
            Http::withToken($this->token)
                ->post($this->callback, [
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                ])
                ->throw();
        }
    }

    protected function local(): void
    {
        try {
            if (empty($this->scanner->host)) {
                throw new TimelogsFetcherException('Device connection not configured.');
            }

            $timelogs = $this->fetcher->fetchTimelogs($this->from, $this->to)->map(fn ($entry) => [
                'device' => $this->host,
                'uid' => $entry['uid'],
                'time' => $entry['time'],
                'state' => $entry['state'],
                'mode' => $entry['mode'],
            ]);

            app(UpsertTimelogs::class, [
                'scanner' => $this->scanner,
                'timelogs' => $timelogs,
                'month' => Carbon::parse($this->month),
                'user' => $this->user,
                'chunkSize' => $this->chunkSize,
            ])->execute();

            if ($this->notify) {
                Notification::make()
                    ->success()
                    ->title('Fetch successful')
                    ->body(
                        str(<<<HTML
                            Timelogs of <i>{$this->scanner->name}</i> has been successfully fetched from the device <br>
                            <i>You may have to wait for a bit before the employees' records are updated</i>
                        HTML)
                            ->squish()
                            ->trim()
                            ->toHtmlString()
                    )
                    ->sendToDatabase($this->user, true);
            }
        } catch (TimelogsFetcherException $exception) {
            Notification::make()
                ->danger()
                ->title('Fetch failed')
                ->body(str("Errors occurred <i>{$this->scanner->name}</i>: <br> ".$exception->getMessage())->toHtmlString())
                ->sendToDatabase($this->user, true);
        }
    }
}
