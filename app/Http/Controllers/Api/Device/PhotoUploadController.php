<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DevicePhotoUploadRequest;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PhotoUploadController extends Controller
{
    public function __construct(
        protected DeviceAttendanceService $deviceAttendanceService,
    ) {}

    public function __invoke(DevicePhotoUploadRequest $request)
    {
        $validated = $request->validated();

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
                'message' => 'Failed to upload photo.',
            ], 422);
        }
    }
}
