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

Route::get('/test1', [App\Http\Controllers\WelcomeController::class, 'index1'])->name('index1');
Route::get('/test2', [App\Http\Controllers\WelcomeController::class, 'index2'])->name('index2');

Route::get('/getpasien/{rm}', [App\Http\Controllers\Pasien\PasienController::class, 'getPasien'])->name('api.getPasien');
