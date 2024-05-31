<?php

namespace App\Services;

use App\Models\Scanner;
use App\Services\TimelogsFetcher\TimelogsFetcherException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class TimelogFetcher
{
    const SCRIPT = 'downloader.py';

    protected readonly string $python;

    protected readonly string $script;

    protected readonly string $ping;

    public function __construct(
        protected Scanner $scanner
    ) {
        $this->python = trim(`which python3` ?? `which python`);

        $this->script = base_path(self::SCRIPT);

        $this->ping = is_string(`which ping`);
    }

    public function fetchTimelogs(?string $from = null, ?string $to = null): Collection|array
    {
        $process = Process::forever()->run($this->command($from, $to));

        if ($process->failed()) {
            switch (trim(strtolower($process->output()))) {
                case 'unauthenticated': throw new TimelogsFetcherException(
                    $this->ping ? 'Invalid password provided.' : 'Cannot reach device or invalid password provided.'
                );
                case 'timed out': throw new TimelogsFetcherException(
                    'Timed out.'.($this->ping ? 'Device may be unreachable' : '')
                );
                default: throw new TimelogsFetcherException(
                    $process->output() ?? $process->errorOutput()
                );
            }
        }

        return collect(explode("\n", $process->output()))
            ->filter()
            ->map(fn ($d) => json_decode($d, true));
    }

    protected function command(?string ...$args): array
    {
        if (empty($this->python)) {
            throw new TimelogsFetcherException(
                'Python interpreter not found. Please install Python.'
            );
        }

        $command = [$this->python, $this->script, '-T 300', $this->scanner->host];

        if ($this->scanner->port) {
            $args[] = '-P';
            $args[] = $this->scanner->port;
        }

        if ($this->scanner->password) {
            $args[] = '-K';
            $args[] = $this->scanner->password;
        }

        if (! $this->ping) {
            $args[] = '--no-ping';
        }

        return array_merge($command, array_diff($args, ['']));
    }
}

namespace App\Services\TimelogsFetcher;

use Exception;

class TimelogsFetcherException extends Exception
{
}
