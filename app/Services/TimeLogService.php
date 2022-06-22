<?php

namespace App\Services;

use App\Actions\FileImport\InsertTimeLogs;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\TimeLog;
use App\Pipes\CheckNumericUid;
use App\Pipes\CheckStateEntries;
use App\Pipes\Chunk;
use App\Pipes\Sanitize;
use App\Pipes\SplitAttlogString;
use App\Pipes\TransformTimeLogData;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\File;

class TimeLogService implements Import
{
    public function __construct(
        private Repository $repository,
    ) { }

    public function dates()
    {
        return [
            'date' => today()->startOfWeek()->format('Y-m-d'),
            'month' => today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'),
            'from' => today()->day > 15 ? today()->startOfMonth()->format('Y-m-d') : today()->subMonth()->startOfMonth()->format('Y-m-d'),
            'to' => today()->day > 15 ? today()->endOfMonth()->format('Y-m-d') : today()->subMonth()->setDay(15)->format('Y-m-d'),
            'period' => today()->day > 15 ? '1st' : 'full',
        ];
    }

    public function validate(Request $request): bool
    {
        return app(Pipeline::class)
            ->send((object) [
                'lines' => $file = File::lines($request->file)->filter()->unique(),
                'data' => app(SplitAttlogString::class)->split($file),
                'error' => null,
            ])->through([
                CheckNumericUid::class,
                CheckStateEntries::class,
            ])->then(function ($result) {

                return $result->error ? ! ($this->error = $result->error) : true;

            });
    }

    public function error(): string
    {
        return $this->error;
    }

    public function parse(Request $request): void
    {
        app(Pipeline::class)
            ->send(File::lines($request->file))
            ->through([
                Sanitize::class,
                SplitAttlogString::class,
                TransformTimeLogData::class,
                Chunk::class,
            ])->then(fn ($d) => $d->each(function ($chunked) {

                app(InsertTimeLogs::class)($chunked->toArray());

            }));
    }

    public function accept(mixed &$accept): mixed
    {
        $accept->time->setHour($accept->time->clone()->setHour($accept->employee->getSchedule($accept->time)->in)->hour);

        $accept->time->setMinutes(random_int(-TimeLog::GRACE_PERIOD, 0));

        $accept->persist = true;

        return $accept;
    }
}
