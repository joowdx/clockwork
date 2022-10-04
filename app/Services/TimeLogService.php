<?php

namespace App\Services;

use App\Actions\FileImport\InsertTimeLogs;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Pipes\CheckNumericUid;
use App\Pipes\CheckStateEntries;
use App\Pipes\Chunk;
use App\Pipes\Sanitize;
use App\Pipes\SplitAttlogString;
use App\Pipes\TransformTimeLogData;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
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

    public function validate(UploadedFile $file): bool
    {
        return app(Pipeline::class)
            ->send((object) [
                'lines' => $file = File::lines($file)->filter()->unique(),
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

    public function parse(UploadedFile $file): void
    {
        app(Pipeline::class)
            ->send(File::lines($file))
            ->through([
                Sanitize::class,
                SplitAttlogString::class,
                TransformTimeLogData::class,
                Chunk::class,
            ])->then(fn ($d) => $d->each(function ($chunked) {

                app(InsertTimeLogs::class)($chunked->toArray());

            }));
    }

    public function logsForTheDay(Employee $employee, Carbon $date): array
    {
        $logs = $employee->logsForTheDay($date);

        return [
            'in1' => $in1 = $this->filterTime($logs, $employee->shift->shift->in1, lead: 1),
            'out1' => $out1 = $this->filterTime($logs->reject(fn ($e) => in_array($e->id, [$in1?->id])), $employee->shift->shift->out1, trail: -1),
            'in2' => $in2 = $this->filterTime($logs->reject(fn ($e) => in_array($e->id, [$in1?->id, $out1?->id])), $employee->shift->shift->in2, lead: -1),
            'out2' => $this->filterTime($logs->reject(fn ($e) => in_array($e->id, [$in1?->id, $out1?->id, $in2?->id])), $employee->shift->shift->out2, trail: 1),
        ];
    }

    protected function filterTime(Collection $logs, string $time, int $range = 2, int $lead = 0, int $trail = 0): mixed
    {
        [ $hour, $minute ] = explode(':', $time);

        if (! $lead && ! $trail) {
            // return $logs->filter(fn ($-e) => $e->time->diffInHours($e->time->clone()->startOfDay()->setHours($hour)->setMinutes($minute)) <= $range)->first();
        }

        if ($lead) {
            return match ($lead) {
                1 =>  $logs->filter(fn ($e) => $e->time->clone()->setHours($hour)->setMinutes($minute)->diffInHours($e->time) <= $range)->first(),
                default =>  $logs->filter(fn ($e) => $e->time->clone()->setHours($hour)->setMinutes($minute)->diffInHours($e->time) <= $range)->first()
            };
        } else {
            return match ($trail) {
                1 =>  $logs->filter(fn ($e) => $e->time->clone()->setHours($hour)->setMinutes($minute)->diffInHours($e->time) <= $range)->first(),
                default =>  $logs->filter(fn ($e) => $e->time->clone()->setHours($hour)->setMinutes($minute)->diffInHours($e->time) <= $range)->first(),
            };
        }
    }
}
