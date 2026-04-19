<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CapacitorDataController extends Controller
{
   public function __construct(
      protected DeviceAttendanceService $deviceAttendanceService,
   ) {
   }

   /**
    * Get current location from device
    * POST /api/device/location
    */
   public function getLocation(Request $request)
   {
      $validated = $request->validate([
         'latitude' => ['required', 'numeric', 'between:-90,90'],
         'longitude' => ['required', 'numeric', 'between:-180,180'],
         'accuracy' => ['nullable', 'numeric'],
      ]);

      try {
         return response()->json([
            'success' => true,
            'data' => [
               'latitude' => $validated['latitude'],
               'longitude' => $validated['longitude'],
               'accuracy' => $validated['accuracy'] ?? null,
               'timestamp' => now()->toIso8601String(),
            ]
         ]);
      } catch (\Throwable $e) {
         Log::warning('Failed to process device location data.', [
            'user_id' => Auth::id(),
            'exception' => $e->getMessage(),
         ]);

         return response()->json([
            'success' => false,
            'message' => 'Failed to process location data.'
         ], 422);
      }
   }

   /**
    * Save a device barcode scan using the current attendance schema.
    * POST /api/device/barcode
    */
   public function saveBarcodeData(Request $request)
   {
      $validated = $request->validate([
         'barcode_data' => ['required', 'string'],
         'latitude' => ['required', 'numeric', 'between:-90,90'],
         'longitude' => ['required', 'numeric', 'between:-180,180'],
         'timestamp' => ['nullable', 'date_format:Y-m-d H:i:s'],
      ]);

      try {
         $result = $this->deviceAttendanceService->saveBarcodeScan(
            userId: Auth::id(),
            barcodePayload: $validated['barcode_data'],
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            timestamp: $validated['timestamp'] ?? null,
         );

         if (! $result['ok']) {
            return response()->json([
               'success' => false,
               'message' => $result['message'],
            ], $result['status']);
         }

         return response()->json([
            'success' => true,
            'message' => 'Barcode data saved successfully',
            'attendance_id' => $result['attendance']->id,
            'action' => $result['action'],
         ]);
      } catch (\Throwable $e) {
         Log::warning('Failed to save device barcode data.', [
            'user_id' => Auth::id(),
            'exception' => $e->getMessage(),
         ]);

         return response()->json([
            'success' => false,
            'message' => 'Failed to save barcode data.'
         ], 422);
      }
   }

   /**
    * Upload a device photo and attach it to the current attendance record.
    * POST /api/device/photo
    */
   public function uploadPhoto(Request $request)
   {
      $validated = $request->validate([
         'photo' => ['required', 'image', 'max:5120'], // 5MB
         'latitude' => ['nullable', 'numeric', 'between:-90,90'],
         'longitude' => ['nullable', 'numeric', 'between:-180,180'],
      ]);

      try {
         $result = $this->deviceAttendanceService->uploadPhoto(
            userId: Auth::id(),
            photo: $request->file('photo'),
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
         );

         return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'path' => route('attendance.photo', [
               'attendance' => $result['attendance']->id,
               'type' => $result['slot'],
            ], false),
            'attendance_id' => $result['attendance']->id,
         ]);
      } catch (\Throwable $e) {
         Log::warning('Failed to upload device attendance photo.', [
            'user_id' => Auth::id(),
            'exception' => $e->getMessage(),
         ]);

         return response()->json([
            'success' => false,
            'message' => 'Failed to upload photo.'
         ], 422);
      }
   }
   /**
    * Request device permissions status
    * GET /api/device/permissions
    */
   public function getPermissionsStatus(Request $request)
   {
      return response()->json([
         'success' => true,
         'permissions' => [
            'camera' => [
               'state' => 'prompt', // 'prompt', 'granted', 'denied'
               'description' => 'Camera access for barcode scanning'
            ],
            'geolocation' => [
               'state' => 'prompt',
               'description' => 'Location access for attendance tracking'
            ]
         ]
      ]);
   }

}
