<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Scanner;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Response;

class EnrollmentExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private User $user
    ) {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $handle = fopen('php://output', 'w');

        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($handle, ['Employee', 'Scanner', 'UID']);

        $enrollments = Enrollment::query()
            ->addSelect([
                'employee' => Employee::selectRaw("TRIM(CONCAT_WS(' ', CONCAT(COALESCE(\"name\"->>'last', ''), ', '), COALESCE(\"name\"->>'first', ''), COALESCE(\"name\"->>'middle', ''), COALESCE(\"name\"->>'extension', ''))) AS name")
                    ->whereColumn('employee_id', 'employees.id')
                    ->limit(1),
                'scanner' => Scanner::select('name')
                    ->whereColumn('scanner_id', 'scanners.id')
                    ->orderBy('name')
                    ->limit(1),
            ])
            ->when(
                in_array(auth()->user()->role, [UserRole::DEPARTMENT_HEAD, UserRole::ADMINISTRATIVE_OFFICER]),
                function ($query) {
                    $query->whereHas('employee', fn ($q) => $q->whereIn('office', $this->user->offices)->active());
                },
                function ($query) {
                    $query->whereHas('scanner', function ($query) {
                        $query->whereHas('users', function ($query) {
                            $query->where('user_id', auth()->id());
                        });
                    });
                }
            )
            ->whereHas('employee', fn ($query) => $query->whereActive(true))
            ->orderBy('employee')
            ->orderBy('scanner')
            ->get();

        foreach ($enrollments as $enrollment) {
            fputcsv($handle, [$enrollment->employee, $enrollment->scanner, $enrollment->uid]);
        }

        fclose($handle);
    }
}
