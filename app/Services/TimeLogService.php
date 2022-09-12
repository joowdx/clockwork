<?php

namespace App\Services;

use App\Actions\FileImport\InsertTimeLogs;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Pipes\CheckNumericUid;
use App\Pipes\CheckStateEntries;
use App\Pipes\Chunk;
use App\Pipes\Sanitize;
use App\Pipes\SplitAttlogString;
use App\Pipes\TransformTimeLogData;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
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

    public function arrivalTime(Employee $employee, Carbon $date, ?string $shift = null): ?Carbon
    {
        return $this->logsForTheDay($employee, $date)->first(fn ($log) => $log->in)?->time;
    }

    public function departureTime(Employee $employee, Carbon $date, ?string $shift = null): ?Carbon
    {
        return $this->logsForTheDay($employee, $date)->first(fn ($log) => $log->out)?->time;
    }

    public function calculateUnderTimeForTheDay(Employee $employee, Carbon $date)
    {

    }

    protected function logsForTheDay(Employee $employee, Carbon $date)
    {
        return $employee->timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->values();
    }

    protected function asdasd(Employee $employee, Carbon $date)
    {
        $employee->hasOne(Schedule::class)->ofMany([
            'id' => 'max'
        ], function ($query) use ($date) {
            $query->active($date);
        })->withDefault(function ($schedule) {
            $schedule->default = true;

            $schedule->days = Schedule::DEFAULT_DAYS;

            $schedule->shift = (object) [
                'in1' => Shift::DEFAULT_IN1,
                'in2' => Shift::DEFAULT_IN2,
                'out1' => Shift::DEFAULT_OUT1,
                'out2' => Shift::DEFAULT_OUT2,
            ];
        });
    }
}
