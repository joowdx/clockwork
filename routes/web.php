<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TimeLogController;
use Illuminate\Support\Facades\Route;

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
    return redirect()->route('login');

    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
});


Route::middleware(['auth', 'verified'])->group(function() {

    Route::middleware(['can:non-readonly'])->group(function () {
        // Route::get('/dashboard', fn () => inertia('dashboard'))->name('dashboard');
        Route::get('/dashboard', fn () => redirect()->route('timelogs.index'))->name('dashboard');
        Route::resource('users', ScannerController::class);
        Route::resource('scanners', ScannerController::class);
        Route::resource('employees', EmployeeController::class)->except(['show']);
        Route::resource('timelogs', TimeLogController::class)->only(['index', 'store']);
        Route::resource('enrollment', EnrollmentController::class)->only(['store', 'destroy']);
        Route::resource('assignment', AssignmentController::class)->only(['store', 'destroy']);
    });

    Route::get('/print/{by}', PrintController::class)->whereIn('by', ['dtr', 'office', 'employee'])->name('print');
    Route::get('/attendance', AttendanceController::class)->name('attendance');
});
