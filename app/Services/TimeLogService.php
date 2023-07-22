<?php

namespace App\Services;

use App\Actions\FileImport\InsertTimeLogs;
use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Employee;
use App\Models\Scanner;
use App\Models\TimeLog;
use App\Pipes\CheckNumericUid;
use App\Pipes\CheckStateEntries;
use App\Pipes\Chunk;
use App\Pipes\RemoveDuplicateTimeLog;
use App\Pipes\Sanitize;
use App\Pipes\SplitAttlogString;
use App\Pipes\TransformTimeLogData;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;

class TimeLogService implements Import
{
    public function __construct(
        private Repository $repository,
    ) {
    }

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
        $this->insert(File::lines($file), true);
    }

    public function insert(Collection|LazyCollection|array $data, bool $fromFile = false)
    {
        app(Pipeline::class)
            ->send(is_array($data) ? collect($data) : $data)
            ->through(
                $fromFile ? [
                    Sanitize::class,
                    SplitAttlogString::class,
                    TransformTimeLogData::class,
                    RemoveDuplicateTimeLog::class,
                    Chunk::class,
                ] : [
                    RemoveDuplicateTimeLog::class,
                    Chunk::class,
                ]
            )->then(fn ($d) => $d->each(function ($chunked) {
                app(InsertTimeLogs::class)($chunked->toArray());
            }));
    }

    public function logsForTheDay(Employee $employee, Carbon $date): array
    {
        $logs = $employee->logsForTheDay($date)->sort(fn ($log) => (int) in_array($log->scanner->name, Scanner::PRIORITIES));

        $in = $logs->filter->in
            ->sortBy('time')
            ->unique(fn ($log) => $log->time->format('Y-m-d H:00').(in_array($log->scanner->name, Scanner::PRIORITIES) ? 'coliseum-x' : $log->scanner->name));

        $out = $logs->filter->out
            ->sortByDesc('time')
            ->unique(fn ($log) => $log->time->format('Y-m-d H:00').(in_array($log->scanner->name, Scanner::PRIORITIES) ? 'coliseum-x' : $log->scanner->name));

        return [
            'in1' => $in1 = $this->filterTime($in, 'in', 'am'),
            'out1' => $out1 = $this->filterTime($out, 'out', 'am'),
            'in2' => $in2 = $this->filterTime($in->reject(fn ($log) => $in1?->time->gt($log->time) || $out1?->time->gt($log->time)), 'in', 'pm'),
            'out2' => $out2 = $this->filterTime($out->reject(fn ($log) => $in2?->time->gt($log->time) || $out1?->time->gt($log->time)), 'out', 'pm'),
            'ut' => $this->calculateUndertime($date, $in1, $out1, $in2, $out2, @request()->weekends['regular'] ? ! request()->weekends['regular'] : $employee->regular),
        ];
    }

    public function time(): mixed
    {
        $request = request();

        $parse = function (string $week) use ($request) {
            if ($request->filled(["$week.am.in", "$week.am.out", "$week.pm.in", "$week.am.out"])) {
                return "{$request->$week['am']['in']}-{$request->$week['am']['out']} {$request->$week['pm']['in']}-{$request->$week['pm']['out']}";
            } elseif ($request->filled(["$week.am.in", "$week.pm.out"])) {
                return "{$request->$week['am']['in']}-{$request->$week['pm']['out']}";
            }

            return 'as required';
        };

        return (object) [
            'weekdays' => $parse('weekdays'),
            'weekends' => $parse('weekends'),
        ];
    }

    protected function filterTime(Collection $logs, string $state = null, string $shift = null): mixed
    {
        return match ($state) {
            'in' => match ($shift) {
                'am' => $logs
                    ->sort(fn ($log) => in_array($log->scanner->name, Scanner::PRIORITIES) ? -1 : 1)
                    ->reject(fn ($log) => $log->time->gte($log->time->clone()->setTime('11', '00')))
                    ->first(fn ($log) => $log->time->clone()->setTime('12', '00')->gt($log->time)),
                'pm' => $logs
                    ->sort(fn ($log) => in_array($log->scanner->name, Scanner::PRIORITIES) ? -1 : 1)
                    ->first(fn ($log) => $log->time->clone()->setTime('11', '00')->lt($log->time)),
                default => null,
            },
            'out' => match ($shift) {
                'am' => $logs
                    ->sort(fn ($log) => in_array($log->scanner->name, Scanner::PRIORITIES) ? 1 : -1)
                    ->first(fn ($log) => $log->time->clone()->setTime('13', '00')->gte($log->time)),
                'pm' => $logs
                    ->sort(fn ($log) => in_array($log->scanner->name, Scanner::PRIORITIES) ? 1 : -1)
                    ->first(fn ($log) => $log->time->clone()->setTime('13', '00')->lte($log->time)),
                default => null,
            },
            default => null,
        };
    }

    protected function calculateUndertime(Carbon $date, ?TimeLog $in1, ?TimeLog $out1, ?TimeLog $in2, ?TimeLog $out2, ?bool $excludeWeekends = true): object|int|null
    {
        $calculate = function () use ($date, $in1, $out1, $in2, $out2) {
            $week = $date->isWeekday() ? 'weekdays' : 'weekends';

            if (request()->filled(["$week.am.in", "$week.am.out", "$week.pm.in", "$week.am.out"])) {
                return (object) [
                    'in1' => $in1ut = max($in1?->time->clone()->setTime(...explode(':', request()->$week['am']['in']))->diffInMinutes($in1->time, false), 0),
                    'in2' => $in2ut = max($in2?->time->clone()->setTime(...explode(':', request()->$week['pm']['in']))->diffInMinutes($in2->time, false), 0),
                    'out1' => $out1ut = max($out1?->time->setSeconds(0)->diffInMinutes($out1->time->setSeconds(0)->clone()->setTime(...explode(':', request()->$week['am']['out'])), false), 0),
                    'out2' => $out2ut = max($out2?->time->setSeconds(0)->diffInMinutes($out2->time->setSeconds(0)->clone()->setTime(...explode(':', request()->$week['pm']['out'])), false), 0),
                    'total' => $in1ut + $out1ut + $in2ut + $out2ut,
                    'count' => $in1 || $out1 || $in2 || $out2,
                    'invalid' => ! $in1 || ! $out1 || ! $in2 || ! $out2,
                ];
            } elseif (request()->filled(["$week.am.in", "$week.pm.out"])) {
                return (object) [
                    'in1' => $in1ut = max($in1?->time->clone()->setTime(...explode(':', request()->$week['am']['in']))->diffInMinutes($in1->time, false), 0),
                    'out2' => $out2ut = max($out2?->time->setSeconds(0)->diffInMinutes($out2->time->setSeconds(0)->clone()->setTime(...explode(':', request()->$week['pm']['out'])), false), 0),
                    'total' => $in1ut + $out2ut,
                    'count' => $in1 || $out2,
                    'invalid' => ! $in1 || ! $out2,
                ];
            }

            return 0;
        };

        return match ($date->isWeekday()) {
            true => $calculate(),
            false => $excludeWeekends ? null : $calculate(),
            default => null,
        };
    }
}
