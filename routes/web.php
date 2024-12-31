<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\SocialiteController;
use App\Http\Middleware\OauthAuthorization;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('test', fn () => 'Hello World!')->name('test');

Route::controller(DownloadController::class)
    ->prefix('download')
    ->group(function () {
        Route::get('export/{export}', 'export')->name('download.export');
        Route::get('attachment/{attachment}', 'attachment')->name('download.attachment');
    });

Route::controller(SocialiteController::class)
    ->prefix('oauth')
    ->middleware(OauthAuthorization::class)
    ->group(function () {
        Route::match(['get', 'post'], 'callback/{provider}', 'processCallback')->name('oauth.callback');
        Route::get('{provider}', 'redirectToProvider')->name('socialite.filament.app.oauth.redirect');
    });
