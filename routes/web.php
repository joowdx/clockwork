<?php

use App\Http\Controllers\BiometricsController;
use App\Http\Controllers\PrintPreviewController;
use App\Http\Controllers\TimeLogsController;
use Illuminate\Foundation\Application;
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
});


Route::middleware(['auth', 'verified'])->group(function() {
    // Route::get('/dashboard', fn () => inertia('dashboard'))->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('timelogs.index'))->name('dashboard');
    Route::get('/printpreview', PrintPreviewController::class)->name('printpreview');
    Route::resource('biometrics', BiometricsController::class)->except(['edit', 'create']);
    Route::resource('timelogs', TimeLogsController::class)->only(['index', 'store']);
});
