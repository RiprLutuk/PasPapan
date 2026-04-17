<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'attendance.require_face_enrollment'],
            [
                'value' => '0',
                'group' => 'attendance',
                'type' => 'boolean',
                'description' => 'Require Face ID enrollment before attendance',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'attendance.require_face_enrollment')->delete();
    }
};
