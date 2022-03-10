<?php

use App\Http\Controllers\BiometricsController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\TimeLogsController;
use App\Models\Employee;
use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);

    // return dd(Employee::with([
    //     'logs' => fn ($e) => $e->whereMonth('time', 2)->whereYear('time', 2022)
    // ])->first()?->logs->map(fn ($e) => $e->time));

    $columns = array_flip(explode(',', strtoupper((string) File::lines('../PGSO.csv')->first())));

    $indices = Employee::all()->map(fn ($e) => $e->user_id . '.' . $e->biometrics_id);

    $user = Auth::user() ?? User::find(1);

    File::lines('../PGSO.csv')
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
            'created_at' => now(),
            'updated_at' => now(),
            'user_id' => $user->id, ###############################
        ])
        ->reject(fn ($e) => $indices->contains($user->id. '.' . $e['biometrics_id'])) ###############################
        ->chunk(1000)
        ->map(fn ($e) => $e->toArray())
        ->each(fn ($e) => DB::transaction(fn () => Employee::insert($e)));

    File::lines('../1_attlog.dat')
        ->filter(fn ($e) => $e)
        ->map(fn ($e) => explode("\t", $e))
        ->map(fn ($e) => [
            'biometrics_id' => trim($e[0]),
            'time' => Carbon::createFromTimeString($e[1]),
            'state' => join('', collect(array_slice($e, 2))->map(fn ($e) => $e > 1 ? 1 : $e)->toArray()),
            'created_at' => now(),
            'updated_at' => now(),
            'user_id' => $user->id, ###############################
        ])
        ->reject(fn ($e) => $user->latest?->time->gte($e['time']))
        ->chunk(1000)
        ->map(fn ($e) => $e->toArray())
        ->each(fn ($e) => DB::transaction(fn () => TimeLog::insert($e)));

    return dd(TimeLog::count(), Employee::count());

    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/sample', SampleController::class);

// Route::get('/biometrics', BiometricsController::class)->name('biometrics');


Route::middleware(['auth', 'verified'])->group(function() {
    Route::resource('biometrics', BiometricsController::class);
    Route::get('/logs', TimeLogsController::class)->name('timelogs');
});
