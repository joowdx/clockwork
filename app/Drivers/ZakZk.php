<?php

namespace App\Drivers;

use App\Contracts\ScannerDriver;
use Illuminate\Http\Client\ConnectionException;
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

    public function setClient(string $clientHost, int|string $clientPort = 4370): void
    {
        $this->clientHost = $clientHost;

        $this->clientPort = $clientPort;
    }

    public function setClientHost(string $clientHost): void
    {
        $this->clientHost = $clientHost;
    }

    public function setClientPort(string $clientPort): void
    {
        $this->clientPort = $clientPort;
    }

    protected function checkConnection(?string $host): mixed
    {
        $ch = curl_init($host);

        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $httpcode !== 0;
    }

    protected function checkClient(): void
    {
        $connected = $this->checkConnection("http://$this->clientHost:$this->clientPort");

        if (! $connected) {
            throw new ConnectionException('Device is unreachable.');
        }
    }

    public function getAttlogs(): array
    {
        $this->checkClient();

        try {
            $data = Http::get(
                url: 'http://'.$this->host.':'.$this->port,
                query: [
                    'format' => 'json',
                    'ip' => $this->clientHost,
                    'port' => $this->clientPort,
                ]
            )->json();
        } catch (ConnectionException) {
            throw new ConnectionException('ZakZk server is unreachable.');
        }

        return $data;
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
