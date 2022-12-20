<?php

namespace App\Drivers;

use App\Contracts\ScannerDriver;
use Exception;
use Illuminate\Support\Facades\Http;

class ZakZk implements ScannerDriver
{
    protected readonly string $host;

    protected readonly int|string $port;

    public function __construct(
        protected string $clientHost,
        protected int|string|null $clientPort = 4370,
    ) {
        $this->host = config('zakzk.host');

        $this->port = (int) config('zakzk.port');
    }

    public function setClient(string $clientHost, int|string $clientPort = 4370)
    {
        $this->clientHost = $clientHost;

        $this->clientPort = $clientPort;
    }

    public function setClientHost(string $clientHost)
    {
        $this->clientHost = $clientHost;
    }

    public function setClientPort(string $clientPort)
    {
        $this->clientPort = $clientPort;
    }

    public function getAttlogs(): array
    {
        return Http::get(
            url: 'http://'.$this->host.':'.$this->port,
            query: [
                'format' => 'json',
                'ip' => $this->clientHost,
                'port' => $this->clientPort,
            ]
        )->onError(fn () => throw new Exception('ZakZk server or scanner terminal is unreachable.')
        )->json();
    }

    public function getFormattedAttlogs(?string $withScannerId = null): array
    {
        return collect($this->getAttlogs())
            ->map(function ($attlog) use ($withScannerId) {
                if ($withScannerId) {
                    return [
                        'scanner_id' => $withScannerId,
                        'uid' => $attlog['uid'],
                        'time' => $attlog['time'],
                        'state' => $attlog['state'],
                    ];
                }

                return $attlog;
            })->toArray();
    }

    public function syncTime(): void
    {
    }

    public function getUsers(): array
    {
        return [];
    }
}
