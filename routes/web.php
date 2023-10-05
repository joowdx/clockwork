<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssociateUserEmployeeProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\SpecimenController;
use App\Http\Controllers\TimelogController;
use App\Http\Controllers\TimelogsDownloaderController;
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

Route::get('account-disallowed', fn () => inertia('Auth/AccountDisallowed', ['user' => auth()->user()]))
    ->middleware(['auth'])
    ->name('account.disallowed');

Route::middleware(['auth:sanctum', 'account.disallowed', 'verified'])->group(function () {
    Route::middleware(['account.disallowed.system'])->group(function () {
        Route::get('/', HomeController::class)->name('home');


        Route::resource('users.signature', SignatureController::class)->only(['store']);
        Route::resource('signature', SignatureController::class)->only(['update']);
        Route::resource('signature.specimens', SpecimenController::class)->only(['store']);
        Route::resource('specimens', SpecimenController::class)->only(['update', 'destroy']);
        Route::resource('users', UserController::class)
            ->middleware(['administrator'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('employees.timelogs', TimelogController::class)->only(['index']);
        Route::resource('employees', EmployeeController::class)->only(['store', 'update', 'destroy']);
        Route::resource('scanners', ScannerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('timelogs', TimelogController::class)->only(['store', 'update', 'destroy']);
        Route::resource('enrollment', EnrollmentController::class)->only(['store', 'destroy']);
        Route::resource('assignment', AssignmentController::class)->only(['store', 'destroy']);

        Route::controller(AssociateUserEmployeeProfileController::class)->group(function () {
            Route::post('users/{user}/employee', 'link')->name('user.employee.link');
            Route::delete('users/{user}/employee', 'unlink')->name('user.employee.unlink');
        });

        Route::match(['get', 'post'], 'attendance', [AttendanceController::class, 'index'])
            ->middleware(['administrator'])
            ->name('attendance');
    });

    Route::controller(TimelogsDownloaderController::class)->group(function () {
        Route::post('scanners/{scanner}/download', 'download')->name('scanners.download');
    });

    Route::match(['get', 'post'], 'print/{by}', PrintController::class)->whereIn('by', ['dtr', 'office', 'employee', 'search'])->name('print');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('user/profile', [ProfileController::class, 'show'])->name('profile.show');
});
