<?php

use App\Http\Controllers\Api\Device\BarcodeScanController;
use App\Http\Controllers\Api\Device\LocationController;
use App\Http\Controllers\Api\Device\PermissionsStatusController;
use App\Http\Controllers\Api\Device\PhotoUploadController;
use App\Http\Controllers\Api\WilayahController;
use App\Support\ApiTokenPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:api']);

// Wilayah Data Endpoints
Route::prefix('wilayah')->middleware('throttle:api')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces']);
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies']);
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts']);
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages']);
});

// Capacitor Device API Routes
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('device')->group(function () {
    Route::post('/location', LocationController::class)->middleware('abilities:' . ApiTokenPermission::DEVICE_LOCATION);
    Route::post('/barcode', BarcodeScanController::class)->middleware('abilities:' . ApiTokenPermission::DEVICE_BARCODE);
    Route::post('/photo', PhotoUploadController::class)->middleware('abilities:' . ApiTokenPermission::DEVICE_PHOTO);
    Route::get('/permissions', PermissionsStatusController::class)->middleware('abilities:' . ApiTokenPermission::DEVICE_PERMISSIONS);
});
