<?php

use App\Http\Controllers\ExportController;
use App\Models\Timesheet;
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

Route::get('export/{export}', ExportController::class)->name('export');

Route::get('test', function () {
    $timesheet = Timesheet::find('01jakwnb0sct0kgq5wfb3w8k17');

    $user = $timesheet->employee;

    $data = [
        'timesheets' => [$timesheet],
        'user' => $user,
        'month' => $timesheet->month,
        'period' => $timesheet->span,
        'format' => 'csc',
        'size' => $data['size'] ?? 'folio',
        'certify' => 1,
        'misc' => [
            'calculate' => true,
        ],
    ];

    return view('print.csc', $data);
});
