<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Response;

class TimeLogsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): RedirectResponse|Response
    {
        return inertia('TimeLogs/Index', [
            'employees' => $employees = $request->user()->employees()->sortByName()->with('user')->get(),
            'month' => today()->startOfMonth()->format('Y-m'),
            'period' => 'full',
            'offices' => $employees->unique('office')->map->office->values()->prepend('ALL')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:dat,csv,txt']);

        switch($request->file->extension()) {
            case 'txt' :
            case 'dat' : {
                File::lines($request->file)
                    ->filter(fn ($e) => $e)
                    ->map(fn ($e) => explode("\t", $e))
                    ->map(fn ($e) => [
                        'biometrics_id' => trim($e[0]),
                        'time' => Carbon::createFromTimeString($e[1]),
                        'state' => join('', collect(array_slice($e, 2))->map(fn ($e) => $e > 1 ? 1 : $e)->toArray()),
                        'user_id' => $request->user()->id,
                    ])
                    ->reject(fn ($e) => $request->user()->latest?->time->gte($e['time']))
                    ->chunk(1000)
                    ->map(fn ($e) => $e->toArray())
                    ->each(fn ($e) => DB::transaction(fn () => TimeLog::insert($e)));
                break;
            }
            default: {

                $columns = array_flip(explode(',', strtoupper((string) File::lines($request->file)->first())));

                File::lines($request->file)
                    ->skip(1)
                    ->filter(fn ($e) => $e)
                    ->map(fn($e) => str($e)->explode(','))
                    ->map(fn ($e) => [
                        'biometrics_id' => $e[$columns['SCANNER ID']],
                        'name' => json_encode([
                            'last' => $e[$columns['FAMILY NAME']],
                            'first' => $e[$columns['GIVEN NAME']],
                            'middle' => $e[$columns['MIDDLE INITIAL']],
                            'extension' => $e[$columns['NAME EXTENSION']],
                        ]),
                        'regular' => (bool) $e[$columns['REGULAR']],
                        'office' => $e[$columns['OFFICE']],
                        'user_id' => $request->user()->id,
                    ])
                    ->chunk(1000)
                    ->map(fn ($e) => $e->toArray())
                    ->each(fn ($e) => DB::transaction(fn () => Employee::upsert($e, ['biometrics_id', 'user_id'])));
            }
        }

        return redirect()->back();
    }
}
