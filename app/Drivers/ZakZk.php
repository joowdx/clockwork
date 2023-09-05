<?php

namespace App\Drivers;

use App\Contracts\ScannerDriver;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ZakZk implements ScannerDriver
{
    protected readonly string $host;

    protected readonly int|string $port;

    public function __construct(
        protected string $clientHost,
        protected int|string|null $clientPort,
    ) {
        $this->host = config('zakzk.host');

        $this->port = (int) config('zakzk.port');

        $this->setClient($clientHost, $clientPort);
    }

    public function setClient(string $clientHost, int|string|null $clientPort): void
    {
        $this->setClientHost($clientHost);

        $this->setClientPort($clientPort);
    }

    public function setClientHost(string $clientHost): void
    {
        $this->clientHost = $clientHost;
    }

    public function setClientPort(?int $clientPort): void
    {
        $this->clientPort = $clientPort ?? 4370;
    }

    protected function checkClient(): void
    {
        #check for tcp connection
        $tcp = @fsockopen($this->clientHost, $this->clientPort, timeout: 1);

        if ($tcp) {
            fclose($tcp);

            return;
        }

        #check for udp connection
        $udp = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($udp === false) {
            throw new Exception("Failed to create UDP socket.");
        }

        socket_set_nonblock($udp);

        $connection = @socket_connect($udp, $this->clientHost, $this->clientPort);

        if ($connection === false) {
            socket_close($udp);

            throw new ConnectionException("Device is unreachable");
        }

        socket_close($udp);

        return;
    }

    public function getAttlogs(): array
    {
        // $this->checkClient();

        try {
            $response = Http::get(
                url: 'http://'.$this->host.':'.$this->port,
                query: [
                    'format' => 'json',
                    'ip' => $this->clientHost,
                    'port' => $this->clientPort,
                ]
            );

           if ($response->serverError()) {
                throw new ConnectionException(code: 21);
           }

            $data = $response->json();
        } catch (ConnectionException $ex) {
            throw new ConnectionException(
                $ex->getCode() === 21 ? 'ZakZk server and device connection error.' : 'Connection/timeout error.'
            );
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
