<?php

namespace App\Jobs;

use App\Events\EmployeesImportation;
use App\Events\EmployeesImported;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportEmployees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $file,
        private string $name,
        private User $user,
        private Carbon $time,
    ) {
        $this->onQueue('process');
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeeService $service): void
    {
        $data = null;

        DB::transaction(function () use (&$data, $service) {
            $data = $service->parse($this->file);
        });

        EmployeesImportation::dispatch(
            $this->user,
            'success',
            ($count = $data?->flatMap(fn ($e) => $e['scanners'])->count()) . ' ' .  str('enrollment')->plural($count) . ' for ' .
            $data?->count() . ' ' . str('employee')->plural($data?->count()) . ' in ' .
            ($count = $data?->map->employee->countBy('office')->count()) . ' ' . str('office')->plural($count) .
            " have been succesfully imported from '{$this->name}'.",
            $this->time,
            now()->diffInSeconds($this->time),
        );

        EmployeesImported::dispatch($this->user, $data?->collect());

        File::delete($this->file);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        File::delete($this->file);

        EmployeesImportation::dispatch(
            $this->user,
            "error",
            trim($exception->getMessage()),
            $this->time,
            now()->diffInSeconds($this->time),
        );
    }
}
