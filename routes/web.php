<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\UserController;
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

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('home'))->name('dashboard');
    Route::get('home', HomeController::class)->name('home');
    Route::resource('scanners', ScannerController::class);
    Route::resource('users', UserController::class);
    Route::resource('employees', EmployeeController::class)->only(['store', 'update', 'destroy']);
    Route::resource('timelogs', TimeLogController::class)->only(['store']);
    Route::resource('enrollment', EnrollmentController::class)->only(['store', 'destroy']);
    Route::resource('assignment', AssignmentController::class)->only(['store', 'destroy']);

    // Route::get('/attendance', AttendanceController::class)->name('attendance');

    Route::controller(ScannerController::class)->group(function () {
        Route::post('scanners/{scanner}/download', 'download')->name('scanners.download');
    });

    Route::match(['get', 'post'], 'print/{by}', PrintController::class)->whereIn('by', ['dtr', 'office', 'employee', 'search'])->name('print');
});
