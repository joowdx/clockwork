<?php

namespace App\Services;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Models\Enrollment;
use App\Models\TimeLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $time = now()->startOfMillennium();

        return File::lines($request->file)->filter()
            ->map(fn ($e) => explode("\t", $e))
            ->every(function ($line) use(&$time) {
                try {
                    $log = Carbon::parse($line[1]);

                    $check = $log->gte($time);

                    $time = $log;

                    return is_numeric($line[0]) && $check &&
                        join('', collect(array_slice($line, 2))->map(fn ($d) => preg_replace('/[0-9]+/', 0, $d))->toArray()) === '0000';
                } catch (Exception) {
                    return false;
                }
            });
    }

    public function error(): string
    {
        return 'PLEASE CHECK THE IMPORTED FILE AND MAKE SURE IT IS VALID AND NOT TAMPERED WITH!';
    }

    public function parse(Request $request): void
    {
        File::lines($request->file)
            ->filter()
            ->unique()
            ->map(fn ($e) => explode("\t", $e))
            ->map(fn ($e) => $this->transformImportData($e, $request->scanner))
            ->chunk(1000)
            ->each(function ($chunk) {

                $keys = $chunk->unique('uid', 'scanner_id')->mapWithKeys(fn ($e) => [
                    $e['uid'] => Enrollment::firstWhere([
                        'uid' => $e['uid'],
                        'scanner_id' => $e['scanner'],
                    ])?->id
                ])->filter()->toArray();

                $this->repository->upsert(
                    $chunk->map(fn ($e) => [...$e, 'enrollment_id' => @$keys[$e['uid']]])->filter(fn ($r) => $r['enrollment_id'])->toArray(),
                    ['enrollment_id', 'time', 'state']
                );
            });
    }

    public function accept(mixed &$accept): mixed
    {
        $accept->time->setHour($accept->time->clone()->setHour($accept->employee->getSchedule($accept->time)->in)->hour);

        $accept->time->setMinutes(random_int(-TimeLog::GRACE_PERIOD, 0));

        $accept->persist = true;

        return $accept;
    }

    private function transformImportData(array $record, string $scanner): array
    {
        return [
            'id' => str()->orderedUuid()->toString(),
            'uid' => trim($record[0]),
            'scanner' => strtolower($scanner),
            'time' => Carbon::createFromTimeString($record[1]),
            'state' => join('', collect(array_slice($record, 2))->map(fn ($record) => $record > 1 ? 1 : $record)->toArray()),
        ];
    }
}
