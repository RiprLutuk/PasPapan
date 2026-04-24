<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceBarcodeScanRequest;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BarcodeScanController extends Controller
{
    public function __construct(
        protected DeviceAttendanceService $deviceAttendanceService,
    ) {}

    public function __invoke(DeviceBarcodeScanRequest $request)
    {
        $validated = $request->validated();

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
                'message' => 'Failed to save barcode data.',
            ], 422);
        }
    }
}
