<?php

namespace App\Services;

use App\Models\Scanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DownloaderService
{
    const SCRIPT = 'downloader.py';

    private $python;

    private $script;

    private $ping;

    public function __construct(
        private Scanner $scanner
    ) {
        $this->python = trim(`which python3` ?? `which python`);

        $this->script = base_path('python/'.self::SCRIPT);

        $this->ping = is_string(`which ping`);
    }

    public function setScanner(Scanner $scanner): self
    {
        $this->scanner = $scanner;

        return $this;
    }

    public function getScanner(): Scanner
    {
        return $this->scanner;
    }

    public function command()
    {
        if (empty($this->python)) {
            throw new \RuntimeException("Python interpreter not found. Please install Python.");
        }

        $args = "";

        if ($this->scanner->port) {
            $args.="-P {$this->scanner->port} ";
        }

        if ($this->scanner->password) {
            $args.="-K {$this->scanner->password} ";
        }

        return "$this->python $this->script {$this->scanner->ip_address} $args";
    }

    public function getAttendance(string $from = null, string $to = null, bool $array = true): Collection|array
    {
        $command = trim($this->command() . " $from $to");

        $output = exec($command, $response, $code);

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
