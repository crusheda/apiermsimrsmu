<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Whatsapp\JadwalDokterController;
use App\Http\Controllers\BPJS\IcareController;
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
    Route::get('/data/master/dokter', [App\Http\Controllers\LIS\LISController::class, 'masterDokter'])->name('api.informasi.masterDokter');
    Route::get('/data/master/tindakan', [App\Http\Controllers\LIS\LISController::class, 'masterTindakan'])->name('api.informasi.masterTindakan');
    Route::get('/data/order/lab', [App\Http\Controllers\LIS\LISController::class, 'getOrderLab'])->name('api.informasi.getOrderLab');
    Route::post('/data/lab/insert', [App\Http\Controllers\LIS\LISController::class, 'insertHasilTestBulk'])->name('api.informasi.insertHasilTestBulk');
    Route::get('/data/order/radiologi', [App\Http\Controllers\PACS\PACSController::class, 'getOrderRad'])->name('api.informasi.getOrderRad');
});

Route::get('/whatsapp/test', function () {
    $token = env('WHATSAPP_TOKEN');
    $phoneId = env('WHATSAPP_PHONE_ID');
    $to = '6281232545545'; // nomor kamu sendiri

    $response = Http::withToken($token)->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'body' => 'Halo Faisal! Ini pesan uji coba dari WhatsApp API Laravel 🚀'
        ],
    ]);

    return $response->json();
});

// ✅ Endpoint utama untuk webhook WhatsApp
Route::post('/whatsapp/webhook', [JadwalDokterController::class, 'handle']);

// ✅ Optional: Untuk verifikasi webhook dari Meta (GET)
Route::get('/whatsapp/webhook', function () {
    $verify_token = env('WHATSAPP_VERIFY_TOKEN');
    $mode = request('hub_mode');
    $token = request('hub_verify_token');
    $challenge = request('hub_challenge');

    if ($mode === 'subscribe' && $token === $verify_token) {
        return response($challenge, 200);
    }

    return response('Forbidden', 403);
});

// Route::get('/test1', [App\Http\Controllers\WelcomeController::class, 'index1'])->name('index1');
// Route::get('/test2', [App\Http\Controllers\WelcomeController::class, 'index2'])->name('index2');

// Route::get('/getpasien/{rm}', [App\Http\Controllers\Pasien\PasienController::class, 'getPasien'])->name('api.getPasien');
// Route::get('/surkon/table', [App\Http\Controllers\RegOnline\surkonController::class, 'table'])->name('surkon.table');
