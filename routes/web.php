<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssociateUserEmployeeProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveCaptureController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\SpecimenController;
use App\Http\Controllers\TimelogController;
use App\Http\Controllers\DownloaderController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EmployeeToken;
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

Route::middleware('guest')->group(function () {
    Route::controller(PinController::class)->middleware('throttle')->group(function () {
        Route::get('employees/{employee}/pin', 'setup')->name('pin.setup');
        Route::patch('employees/{employee}/pin', 'check')->name('pin.check');
        Route::post('employees/{employee}/pin', 'initialize')->name('pin.initialize');
        Route::put('employees/{employee}/pin', 'change')->name('pin.change');
        Route::delete('employees/{employee}/pin', 'reset')->name('pin.reset');
    });

    Route::controller(QueryController::class)->group(function () {
        Route::match(['get', 'post'], 'query/search', 'search')->name('query.search');
        Route::get('query/result/{employee}', 'result')->middleware(EmployeeToken::class)->name('query.result');
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('account-disallowed', fn () => inertia('Auth/AccountDisallowed'))
        ->name('account.disallowed');

    Route::get('password-reset', fn () => inertia('Auth/PasswordReset'))->name('password-reset');

    Route::middleware(['account.disallowed', 'required-password-reset'])->group(function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::get('user/profile', 'show')->name('profile.show');
            Route::put('user/profile', 'update')->name('profile.update');
        });

        Route::controller(ConfigurationController::class)->name('configuration.')->prefix('configuration')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('alert', 'alert')->name('set.alert');
        });

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

            Route::controller(LiveCaptureController::class)->group(function () {
                Route::post('capture/{scanner}', 'start')->name('scanner.capture.start');
            });

            Route::match(['get', 'post'], 'attendance', [AttendanceController::class, 'index'])
                ->middleware(['administrator'])
                ->name('attendance');
        });

        Route::controller(DownloaderController::class)->group(function () {
            Route::post('scanners/{scanner}/download', 'download')->name('scanners.download');
        });

        Route::match(['get', 'post'], 'print/{by}', PrintController::class)->whereIn('by', ['dtr', 'office', 'employee', 'search'])->name('print');
    });
});
