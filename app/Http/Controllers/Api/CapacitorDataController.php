<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CapacitorDataController extends Controller
{
   /**
    * Get current location from device
    * POST /api/device/location
    */
   public function getLocation(Request $request)
   {
      $validated = $request->validate([
         'latitude' => ['required', 'numeric'],
         'longitude' => ['required', 'numeric'],
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
      } catch (\Exception $e) {
         return response()->json([
            'success' => false,
            'message' => 'Failed to process location data: ' . $e->getMessage()
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
         'latitude' => ['nullable', 'numeric'],
         'longitude' => ['nullable', 'numeric'],
         'timestamp' => ['nullable', 'date_format:Y-m-d H:i:s'],
      ]);

      try {
         $barcode = Barcode::firstWhere('value', $validated['barcode_data']);

         if (!$barcode) {
            return response()->json([
               'success' => false,
               'message' => 'Invalid barcode',
            ], 422);
         }

         $attendance = Attendance::firstOrNew([
            'user_id' => Auth::id(),
            'date' => now()->format('Y-m-d'),
         ]);

         if (
            $attendance->exists &&
            in_array($attendance->status, ['sick', 'excused', 'permission', 'leave'], true) &&
            $attendance->approval_status === Attendance::STATUS_APPROVED
         ) {
            return response()->json([
               'success' => false,
               'message' => 'Attendance is blocked because the user is on approved leave.',
            ], 422);
         }

         $timestamp = isset($validated['timestamp'])
            ? Carbon::createFromFormat('Y-m-d H:i:s', $validated['timestamp'])
            : now();

         if (is_null($attendance->time_in)) {
            $attendance->fill([
               'barcode_id' => $barcode->id,
               'time_in' => $timestamp,
               'latitude_in' => $validated['latitude'] ?? $attendance->latitude_in,
               'longitude_in' => $validated['longitude'] ?? $attendance->longitude_in,
               'status' => $attendance->status === 'absent' ? 'present' : ($attendance->status ?: 'present'),
            ]);
            $action = 'check_in';
         } elseif (is_null($attendance->time_out)) {
            $attendance->fill([
               'barcode_id' => $attendance->barcode_id ?? $barcode->id,
               'time_out' => $timestamp,
               'latitude_out' => $validated['latitude'] ?? $attendance->latitude_out,
               'longitude_out' => $validated['longitude'] ?? $attendance->longitude_out,
            ]);
            $action = 'check_out';
         } else {
            return response()->json([
               'success' => false,
               'message' => 'Attendance for today is already complete.',
            ], 409);
         }

         $attendance->save();
         Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));

         return response()->json([
            'success' => true,
            'message' => 'Barcode data saved successfully',
            'attendance_id' => $attendance->id,
            'action' => $action,
         ]);
      } catch (\Exception $e) {
         return response()->json([
            'success' => false,
            'message' => 'Failed to save barcode data: ' . $e->getMessage()
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
         'latitude' => ['nullable', 'numeric'],
         'longitude' => ['nullable', 'numeric'],
      ]);

      try {
         $path = $request->file('photo')->storePublicly(
            'attendance_photos/' . now()->format('Y/m/d'),
            ['disk' => 'public']
         );

         $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', now()->format('Y-m-d'))
            ->first();

         $attachments = $this->decodeAttachmentPayload($attendance?->attachment);
         $slot = $this->resolvePhotoSlot($attachments);

         if ($attendance) {
            $attendance->update([
               'attachment' => json_encode(array_merge($attachments, [$slot => $path])),
               'latitude_in' => $slot === 'in'
                  ? ($validated['latitude'] ?? $attendance->latitude_in)
                  : $attendance->latitude_in,
               'longitude_in' => $slot === 'in'
                  ? ($validated['longitude'] ?? $attendance->longitude_in)
                  : $attendance->longitude_in,
               'latitude_out' => $slot === 'out'
                  ? ($validated['latitude'] ?? $attendance->latitude_out)
                  : $attendance->latitude_out,
               'longitude_out' => $slot === 'out'
                  ? ($validated['longitude'] ?? $attendance->longitude_out)
                  : $attendance->longitude_out,
            ]);
         } else {
            $attachments[$slot] = $path;
            $attendance = Attendance::create([
               'user_id' => Auth::id(),
               'date' => now()->format('Y-m-d'),
               'attachment' => json_encode($attachments),
               'latitude_in' => $validated['latitude'] ?? null,
               'longitude_in' => $validated['longitude'] ?? null,
               'status' => 'present',
            ]);
         }

         Attendance::clearUserAttendanceCache(Auth::user(), Carbon::parse($attendance->date));

         return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'path' => Storage::url($path),
            'attendance_id' => $attendance->id,
         ]);
      } catch (\Exception $e) {
         return response()->json([
            'success' => false,
            'message' => 'Failed to upload photo: ' . $e->getMessage()
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

   private function decodeAttachmentPayload(?string $attachment): array
   {
      if (!$attachment) {
         return [];
      }

      $decoded = json_decode($attachment, true);

      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
         return $decoded;
      }

      return ['in' => $attachment];
   }

   private function resolvePhotoSlot(array $attachments): string
   {
      if (!isset($attachments['in'])) {
         return 'in';
      }

      if (!isset($attachments['out'])) {
         return 'out';
      }

      return 'out';
   }
}
