<?php

namespace App\Http\Controllers;

use App\Contracts\Import;
use App\Http\Requests\TimeLog\StoreRequest;
use App\Models\Employee;
use App\Models\TimeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        return $employee
            ->timelogs()
            ->with('scanner')
            ->whereDate('time', $request->date)
            ->reorder()
            ->oldest('time')
            ->oldest('id')
            ->get();
    }

    public function store(StoreRequest $request, Import $import)
    {
        if ($request->has('file')) {
            $import->parse($request->file);

            return redirect()->back();
        }

        TimeLog::make()->forceFill($request->validated())->save();
    }

    public function update(Request $request, TimeLog $timelog)
    {
        $validated = $request->validate(['hidden' => 'required|boolean']);

        $timelog->update($validated);
    }

    public function destroy(TimeLog $timelog)
    {
        $timelog->delete();
    }
}
