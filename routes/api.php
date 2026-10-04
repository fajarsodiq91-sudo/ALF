<?php

use App\Http\Controllers\Api\TapController;
use Illuminate\Support\Facades\Route;

// Network RFID/QR readers (ESP32, standalone) report taps here with their device token.
Route::post('tap', TapController::class)->middleware(['tap.device', 'throttle:120,1'])->name('api.tap');
