<?php

namespace App\Services;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Events\TimeLogsProcessed;
use App\Models\TimeLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class TimeLogService implements Import
{
    public function __construct(
        private Repository $repository,
    ) { }

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
        // $this->truncate();

        File::lines($request->file)
            ->filter()
            ->map(fn ($e) => $this->transformImportData(explode("\t", $e)))
            ->dd()
            ->chunk(1000)
            ->map(fn ($e) => $e->toArray())
            ->each(fn ($e) => $this->repository->insert($e));

        // event(new TimeLogsProcessed(auth()->user(), $request->file));
    }

    public function accept(mixed &$accept): mixed
    {
        $accept->time->setHour($accept->time->clone()->setHour($accept->employee->getSchedule($accept->time)->in)->hour);

        $accept->time->setMinutes(random_int(-TimeLog::GRACE_PERIOD, 0));

        $accept->persist = true;

        return $accept;
    }

    private function transformImportData(array $record): array
    {
        return [
            'id' => str()->orderedUuid()->toString(),
            'time' => Carbon::createFromTimeString($record[1]),
            'state' => join('', collect(array_slice($record, 2))->map(fn ($record) => $record > 1 ? 1 : $record)->toArray()),
        ];
    }
}
