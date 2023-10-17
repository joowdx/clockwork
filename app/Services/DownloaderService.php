<?php

namespace App\Services;

use App\Traits\RunsZkScript;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class DownloaderService
{
    use RunsZkScript;

    const SCRIPT = 'downloader.py';

    public function getAttendance(string $from = null, string $to = null, bool $array = true): Collection|array
    {
        $process = Process::timeout(120)->run($this->command($from, $to));

        if ($process->failed()) {
            switch (trim(strtolower($process->output()))) {
                case 'unauthenticated':throw new RuntimeException($this->ping ? 'Invalid password provided.' : 'Cannot reach device or invalid password provided.');
                case 'timed out': throw new RuntimeException('Timed out.'.($this->ping ? 'Device may be unreachable' : ''));
                default: throw new RuntimeException($process->output() ?? $process->errorOutput());
            }
        }

        return collect(explode("\n", $process->output()))
            ->filter()
            ->map(fn ($d) => json_decode($d, true))
            ->when($array, fn ($collection) => $collection->toArray());
    }

    public function getPreformattedAttendance(string $from = null, string $to = null): array
    {
        return collect($this->getAttendance($from, $to, false))
            ->map(function ($attlog) {
                return [
                    'scanner_id' => $this->scanner->id,
                    'uid' => $attlog['uid'],
                    'time' => Carbon::parse($attlog['time']),
                    'state' => $attlog['state'],
                ];
            })->toArray();
    }
}
