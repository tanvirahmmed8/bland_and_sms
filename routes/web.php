<?php

use App\Http\Controllers\CallInfoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return "Bland Call BD SMS";
});

Route::post('/send-call', [CallInfoController::class, 'index']);
Route::get('/call-status-update', [CallInfoController::class, 'CallStatusUpdate'])->name('call.status.update');
Route::get('/info-send-gohighlevel', [CallInfoController::class, 'infoSendGohighlevel'])->name('info.send.gohighlevel');
Route::post('/callback-call', [CallInfoController::class, 'callbackCall']);
Route::post('/callback-call-mm', [CallInfoController::class, 'callbackCallmm']);
Route::post('/callback-call-aptt', [CallInfoController::class, 'callbackCallAptt']);

Route::post('/send-sms', [CallInfoController::class, 'sendSms']);
