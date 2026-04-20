<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;

class PermissionsStatusController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'permissions' => [
                'camera' => [
                    'state' => 'prompt',
                    'description' => 'Camera access for barcode scanning',
                ],
                'geolocation' => [
                    'state' => 'prompt',
                    'description' => 'Location access for attendance tracking',
                ],
            ],
        ]);
    }
}
