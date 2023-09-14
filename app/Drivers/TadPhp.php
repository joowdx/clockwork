<?php

namespace App\Drivers;

use App\Contracts\ScannerDriver;
use Illuminate\Http\Client\ConnectionException;
use TADPHP\Exceptions\ConnectionError;
use TADPHP\TAD;
use TADPHP\TADFactory;

class TadPhp implements ScannerDriver
{
    protected TAD $tad;

    public function __construct(
        protected string $ip,
        protected int|string|null $port = 4370,
    ) {
        $this->tad = (new TADFactory([
            'ip' => $ip,
            'udp_port' => $port,
            'connection_timeout' => 1,
        ]))->get_instance();
    }

    public function getAttlogs(): array
    {
        try {
            $data = $this->tad->get_att_log()->to_array()['Row'];
        } catch (ConnectionError) {
            throw new ConnectionException('Device is unreachable.');
        }

        return $data;
    }

    public function getFormattedAttlogs(string $withScannerId = null): array
    {
        return collect($this->getAttlogs())
            ->map(function ($attlog) use ($withScannerId) {
                if ($withScannerId) {
                    return [
                        'scanner_id' => $withScannerId,
                        'uid' => $attlog['PIN'],
                        'time' => $attlog['DateTime'],
                        'state' => $attlog['Status'],
                    ];
                }

                return [
                    'uid' => $attlog['PIN'],
                    'time' => $attlog['DateTime'],
                    'state' => $attlog['Status'],
                ];
            })->toArray();
    }

    public function syncTime(): void
    {
        $this->tad->set_date();
    }

    public function getUsers(): array
    {
        return [];
    }
}
