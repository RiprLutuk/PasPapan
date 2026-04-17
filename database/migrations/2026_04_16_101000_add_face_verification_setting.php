<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'attendance.require_face_verification'],
            [
                'value' => '1',
                'group' => 'attendance',
                'type' => 'boolean',
                'description' => 'Require Face ID verification during attendance capture',
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'attendance.require_face_verification')->delete();
    }
};
