<?php

use App\Http\Controllers\SampleController;
use App\Models\Employee;
use App\Models\Log;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
    return dd(Employee::with([
        'logs' => fn ($e) => $e->whereMonth('time', 2)->whereYear('time', 2022)
    ])->first()->logs->map(fn ($e) => $e->time));

    // File::lines('../PGSO.csv')->skip(1)->filter(fn($e) => $e)->map(fn($e) => Str::of($e)->explode(','))->each(function ($e) {
    //     Employee::create([
    //         'biometrics_id' => $e[0],
    //         'name' => [
    //             'last' => $e[1],
    //             'first' => $e[2],
    //             'middle' => $e[3],
    //             'extension' => $e[4],
    //         ],
    //         'regular' => (bool) $e[5],
    //         'user_id' => Auth::user()->id ?? 1,
    //     ]);
    // });

    // return File::lines('../attlog.dat')
    //     ->filter(fn ($e) => $e)
    //     ->map(fn ($e) => explode("\t", $e))
    //     // ->first();
    //     ->each(fn ($e) => Log::create([
    //         'biometrics_id' => trim($e[0]),
    //         'time' => Carbon::createFromTimeString($e[1]),
    //         'state' => join(' ', array_slice($e, 2)),
    //     ]));

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
