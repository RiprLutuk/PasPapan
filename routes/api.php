<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\Device\BarcodeScanController;
use App\Http\Controllers\Api\Device\LocationController;
use App\Http\Controllers\Api\Device\OfflineAttendanceSyncController;
use App\Http\Controllers\Api\Device\PermissionsStatusController;
use App\Http\Controllers\Api\Device\PhotoUploadController;
use App\Http\Controllers\Api\Integrations\AttendanceEventController;
use App\Http\Controllers\Api\WilayahController;
use App\Http\Middleware\EnsureEmployeeDeviceApiAccount;
use App\Support\ApiTokenPermission;
use Illuminate\Support\Facades\Route;

Route::get('/user', AuthenticatedUserController::class)->middleware(['auth:sanctum', 'throttle:api']);

// Wilayah Data Endpoints
Route::prefix('wilayah')->middleware('throttle:wilayah')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces']);
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies'])
        ->where('provinceCode', '[0-9]{2}');
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts'])
        ->where('regencyCode', '[0-9]{2}\.[0-9]{2}');
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages'])
        ->where('districtCode', '[0-9]{2}\.[0-9]{2}\.[0-9]{2}');
});

// Legacy device APIs remain until barcode/photo/offline parity and data migration pass.
Route::middleware(['auth:sanctum', EnsureEmployeeDeviceApiAccount::class, 'throttle:api'])->prefix('device')->group(function () {
    Route::post('/location', LocationController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_LOCATION);
    Route::post('/barcode', BarcodeScanController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_BARCODE);
    Route::post('/offline-attendance', OfflineAttendanceSyncController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE);
    Route::post('/photo', PhotoUploadController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_PHOTO);
    Route::get('/permissions', PermissionsStatusController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_PERMISSIONS);
});

Route::prefix('integrations')
    ->middleware(['throttle:attendance-integrations', 'attendance.integration.signature'])
    ->group(function () {
        Route::post('/attendance-events', [AttendanceEventController::class, 'store']);
    });
