<?php

namespace App\Services;

use App\Actions\FileImport\InsertTimeLogs;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\TimeLog;
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
        $logs = $employee->logsForTheDay($date)->sort(fn ($log) => (int) in_array($log->scanner->name, Scanner::PRIORITIES));

        $in = $logs->filter->in
            ->unique(fn ($log) => $log->time->format('Y-m-d H:00') . (in_array($log->scanner->name, Scanner::PRIORITIES) ? 'coliseum-x' : $log->scanner->name))
            ->sortBy('time');

        $out = $logs->filter->out
            ->unique(fn ($log) => $log->time->format('Y-m-d H:00') . (in_array($log->scanner->name, Scanner::PRIORITIES) ? 'coliseum-x' : $log->scanner->name))
            ->sortByDesc('time');

        $in1 = $this->filterTime($in, $employee->shift->shift->in1, TimeLog::IN1);

        $out1 = $this->filterTime($out, $employee->shift->shift->out1, TimeLog::OUT1);

        $in2 = $this->filterTime($in->reject(fn ($log) => $in1?->time->gte($log->time)), $employee->shift->shift->in2, TimeLog::IN2);

        $out2 = $this->filterTime($out->reject(fn ($log) => $out1?->time->gte($log->time)), $employee->shift->shift->out2, TimeLog::OUT2);

        return [...compact('in1', 'in2', 'out1', 'out2'), ...$this->calculateUndertime($employee->shift, $date, $in1, $in2, $out1, $out2)];
    }

    protected function filterTime(Collection $logs, string $time, int $state = null, int $range = 120): mixed
    {
        [ $hour, $minute ] = explode(':', $time);

        return match ($state) {
            TimeLog::IN1 => $logs
                ->sort(fn ($log) => (int) ! in_array($log->scanner->name, Scanner::PRIORITIES))
                ->first(fn ($log) => $log->time->clone()->setTime($hour, $minute)->diffInMinutes($log->time, false) <= $range),
            TimeLog::IN2 => $logs->first(fn ($log) => $log->time->clone()->setTime($hour, $minute)->diffInMinutes($log->time) <= $range),
            TimeLog::OUT1 => $logs->first(fn ($log) => $log->time->diffInMinutes($log->time->clone()->setTime($hour, $minute)) <= $range),
            TimeLog::OUT2 => $logs->first(fn ($log) => $log->time->diffInMinutes($log->time->clone()->setTime($hour, $minute), false) <= $range),
            default => $logs->first(),
        };
    }

    protected function calculateUndertime(Schedule $schedule, Carbon $date, TimeLog $in1 = null, TimeLog $in2 = null, TimeLog $out1 = null, TimeLog $out2 = null): mixed
    {
        [$in1hour, $in1minute] = explode(':', $schedule->shift->in1);

        [$out1hour, $out1minute] = explode(':', $schedule->shift->out1);

        [$in2hour, $in2minute] = explode(':', $schedule->shift->in2);

        [$out2hour, $out2minute] = explode(':', $schedule->shift->out2);

        if (! in_array($date->dayOfWeek, $schedule->days)) {
            return [];
        }

        if ($in1 && $in2 && $out1 && $out2) {

            $in1minutes = $in1->time->setTime($in1hour, $in1minute)->diffInMinutes($in1->time);

            $out1minutes = $out1->time->diffInMinutes($out1->time->setTime($out1hour, $out1minute));

            $in2minutes = $in2->time->setTime($in2hour, $in2minute)->diffInMinutes($in2->time);

            $out2minutes = $out2->time->diffInMinutes($out2->time->setTime($out2hour, $out2minute));

            // dd($in1minutes, $out1minutes, $in2minutes, $out2minutes);

            return [
                'hours' => $in1->time->diffInMinutes(),
                'minutes' => ''
            ];
        } else if ($in1 && $out2) {

            $in1minutes = $in1->time->setTime($in1hour, $in1minute)->diffInMinutes($in1->time, false);

            $out2minutes = $out2->time->diffInMinutes($out2->time->setTime($out2hour, $out2minute), false);

            $total = ($in1minutes >= 0 ? $in1minutes : 0) + ($out2minutes ? 0 >= $out2minutes : 0);

            // if ($in1->time->format('H:i') == '07:47') {
            //     // dd($in1minutes);
            // };

            return [
                // 'hours' => intval($total / 60) > 0 ? intval($total / 60) : '',
                // 'minutes' => $total % 60 > 0 ? $total % 60 : '',
            ];

        } else if ($in1 && $out1) {

        } else if ($in2 && $out2) {

        };

        return [];
    }
}
