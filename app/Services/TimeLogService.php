<?php

namespace App\Services;

use App\Contracts\Import;
use App\Contracts\Repository;
use App\Events\TimeLogsProcessed;
use App\Models\TimeLog;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class TimeLogService implements Import
{
    public function __construct(
        private Repository $repository,
    ) { }

    public function validate(UploadedFile $file): bool
    {
        $time = now()->startOfMillennium();

        return File::lines($file)->filter()
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

    public function parse(UploadedFile $file): void
    {
        $this->truncate();

        File::lines($file)
            ->filter()
            ->map(fn ($e) => $this->repository->transformImportData(explode("\t", $e)))
            ->chunk(1000)
            ->map(fn ($e) => $e->toArray())
            ->each(fn ($e) => $this->repository->insert($e));

        event(new TimeLogsProcessed(auth()->user(), $file));
    }

    public function truncate(): void
    {
        $this->repository->query()->whereUserId(auth()->id())->delete();
    }

    public function accept(mixed &$accept): mixed
    {
        $accept->time->setHour($accept->time->clone()->setHour($accept->employee->getSchedule($accept->time)->in)->hour);

        $accept->time->setMinutes(random_int(-TimeLog::GRACE_PERIOD, 0));

        $accept->persist = true;

        return $accept;
    }
}
