<?php

use App\Http\Controllers\Api\DeviceAuthenticationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UidSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth.basic:,username', 'account.disallowed'])->group(function () {
    Route::post('device/authenticate', [DeviceAuthenticationController::class, 'authenticate']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('device/deauthenticate', [DeviceAuthenticationController::class, 'deauthenticate']);
    Route::delete('device/destroy-all-session', [DeviceAuthenticationController::class, 'destroyAllSession']);
});

Route::get('uid', [UidSearchController::class, '__invoke']);
Route::match(['get', 'post'], 'search', SearchController::class);
