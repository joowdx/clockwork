<?php

namespace App\Services;

use App\Traits\RunsZkScript;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DownloaderService
{
    use RunsZkScript;

    const SCRIPT = 'downloader.py';

    public function getAttendance(string $from = null, string $to = null, bool $array = true): Collection|array
    {
        $output = exec($this->command("$from $to"), $response, $code);

        if ($code > 0) {
            switch (strtolower($output)) {
                case 'unauthenticated': {
                    throw new \RuntimeException($this->ping ? 'Invalid password provided.' : 'Cannot reach device or invalid password provided.');
                }
                default: throw new \RuntimeException($output ?? '');
            }
        }

        return collect($response)
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
