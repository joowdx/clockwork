<?php

namespace App\Actions;

use App\Events\TimelogsSynchronized;
use App\Models\Scanner;
use App\Models\Timelog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpsertTimelogs
{
    public function __construct(
        private readonly Scanner $scanner,
        private readonly Collection $timelogs,
        private readonly Carbon $month,
        private readonly User $user,
        private readonly int $chunkSize = 1000,
    ) {
    }

    public function execute()
    {
        DB::transaction(function () {
            $this->timelogs->chunk($this->chunkSize)->each(function ($entries) {
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

            $this->scanner->update(['synced_at' => now()]);
        });

        TimelogsSynchronized::dispatch(
            $this->scanner,
            $this->user,
            'fetch',
            $this->month,
            $this->timelogs->first()['time'] ?? null,
            $this->timelogs->last()['time'] ?? null,
            $this->timelogs->count(),
            null,
            [
                'host' => $this->scanner->host,
                'port' => $this->scanner->port,
                'pass' => $this->scanner->pass,
            ]
        );
    }
}
