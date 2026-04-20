<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceLocationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function __invoke(DeviceLocationRequest $request)
    {
        $validated = $request->validated();

        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'latitude' => (float) $validated['latitude'],
                    'longitude' => (float) $validated['longitude'],
                    'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to process device location data.', [
                'user_id' => Auth::id(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process location data.',
            ], 422);
        }
    }
}
