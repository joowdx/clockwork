<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TimelogUpdateRequest;
use App\Models\Employee;
use App\Models\Timelog;
use Illuminate\Support\Carbon;

class TimelogController extends Controller
{
    public function update(TimelogUpdateRequest $request, Employee $employee)
    {
        $this->sanitize($request->day(), $employee);

        $this->unhide($request->day(), $employee);

        $finalized = collect();

        $request->timelogs()->each(
            function (Timelog $timelog) use ($finalized, $request) {
                if($timelog->state !== ($state = $request->find($timelog->id)['state'] === 'in' ? 0 : 1)) {
                    $correction = $timelog->replicate()->forceFill([
                        'state' => $state,
                        'official' => false,
                        'timelog_id' => $timelog->id,
                    ]);

                    $correction->save();

                    $finalized->push($correction->fresh());

                    return;
                }

                $finalized->push($timelog);
            }
        );

        $this->hide($request->day(), $employee, $finalized->pluck('id')->toArray());

        return $finalized;
    }

    public function sanitize(Carbon $day, Employee $employee)
    {
        $employee->timelogs()
            ->whereDate('time', $day)
            ->whereOfficial(false)
            ->delete();
    }

    public function hide(Carbon $day, Employee $employee, array $exclude = [])
    {
        $employee->timelogs()
            ->whereDate('time', $day)
            ->whereNotIn('timelogs.id', $exclude)
            ->update(['hidden' => true]);
    }

    public function unhide(Carbon $day, Employee $employee)
    {
        $employee->timelogs()
            ->whereDate('time', $day)
            ->update(['hidden' => false]);
    }
}
