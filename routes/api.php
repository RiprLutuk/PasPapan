<?php

use App\Http\Controllers\Api\CapacitorDataController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:api']);

// Wilayah Data Endpoints
Route::prefix('wilayah')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces']);
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies']);
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts']);
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages']);
});

// Capacitor Device API Routes
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('device')->group(function () {
    Route::post('/location', [CapacitorDataController::class, 'getLocation']);
    Route::post('/barcode', [CapacitorDataController::class, 'saveBarcodeData']);
    Route::post('/photo', [CapacitorDataController::class, 'uploadPhoto']);
    Route::get('/permissions', [CapacitorDataController::class, 'getPermissionsStatus']);
});
