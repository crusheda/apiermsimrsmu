<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::group(['middleware' => ['web', 'auth']], function() {
// });

//-----------------------------------------------------------------    A  P  I    -----------------------------------------------------------------
Route::middleware('auth.custom')->group(function () { // Authorization (Auth Type = Basic Auth) ==> Username & Password SIMGOS
    Route::get('/informasi/ruangan', [App\Http\Controllers\Informasi\RuanganController::class, 'getRuangan'])->name('api.informasi.getRuangan');
    Route::get('/antrean/poli/display', [App\Http\Controllers\Antrean\AntreanController::class, 'getAntreanPoli'])->name('api.informasi.getAntreanPoli');

});

// Route::get('/test1', [App\Http\Controllers\WelcomeController::class, 'index1'])->name('index1');
// Route::get('/test2', [App\Http\Controllers\WelcomeController::class, 'index2'])->name('index2');

// Route::get('/getpasien/{rm}', [App\Http\Controllers\Pasien\PasienController::class, 'getPasien'])->name('api.getPasien');
// Route::get('/surkon/table', [App\Http\Controllers\RegOnline\surkonController::class, 'table'])->name('surkon.table');
