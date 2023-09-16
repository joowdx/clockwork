<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssociateUserEmployeeProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\TwoFactorAuthenticationController;
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

Route::get('account-disallowed', fn () => inertia('Auth/AccountDisallowed', ['user' => auth()->user()]))
    ->middleware(['auth'])
    ->name('account.disallowed');

Route::middleware(['auth:sanctum', 'account.disallowed', 'verified'])->group(function () {
    Route::middleware(['account.disallowed.system'])->group(function () {
        Route::get('dashboard', fn () => redirect()->route('home'))->name('dashboard');
        Route::get('home', HomeController::class)->name('home');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('scanners', ScannerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('employees.timelogs', TimeLogController::class)->only(['index']);
        Route::resource('employees', EmployeeController::class)->only(['store', 'update', 'destroy']);
        Route::resource('timelogs', TimeLogController::class)->only(['store', 'update', 'destroy']);
        Route::resource('enrollment', EnrollmentController::class)->only(['store', 'destroy']);
        Route::resource('assignment', AssignmentController::class)->only(['store', 'destroy']);
        Route::controller(AssociateUserEmployeeProfileController::class)->group(function () {
            Route::post('users/{user}/employee', [AssociateUserEmployeeProfileController::class, 'link'])->name('user.employee.link');
            Route::delete('users/{user}/employee', [AssociateUserEmployeeProfileController::class, 'unlink'])->name('user.employee.unlink');
        });
    });

    Route::controller(ScannerController::class)->group(function () {
        Route::post('scanners/{scanner}/download', 'download')->name('scanners.download');
    });

    Route::match(['get', 'post'], 'print/{by}', PrintController::class)->whereIn('by', ['dtr', 'office', 'employee', 'search'])->name('print');
});
