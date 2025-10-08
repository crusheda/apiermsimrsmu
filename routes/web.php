<?php

use Illuminate\Support\Facades\Route;
use App\Services\WhatsappService;

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

// Route::get('/', function () {
//     return view('welcome');
// });

// INITIALIZATION
// use App\Http\Controllers\DashboardController;

// Auth::routes();
Auth::routes(['register' => false]); // Cannot Access /register
Route::group(['middleware' => ['web', 'auth']], function() {
    // Route::get('/surkon', [App\Http\Controllers\RegOnline\surkonController::class, 'index'])->name('surkon.index');
    // Route::get('/test1', [App\Http\Controllers\WelcomeController::class, 'index1'])->name('index1');
    // Route::get('/test2', [App\Http\Controllers\WelcomeController::class, 'index2'])->name('indx2');

});

Route::get('/wa-test', function (WhatsappService $wa) {
    $response = $wa->sendMessage('6281232545545', 'Halo, ini pesan tes dari Laravel!');
    return $response;
});
