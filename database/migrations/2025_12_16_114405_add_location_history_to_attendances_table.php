<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add new columns
            $table->double('latitude_in')->nullable()->after('shift_id');
            $table->double('longitude_in')->nullable()->after('latitude_in');
            $table->double('latitude_out')->nullable()->after('longitude_in');
            $table->double('longitude_out')->nullable()->after('latitude_out');
        });

        // Migrate existing data (copy old location to check-in location)
        DB::table('attendances')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->update([
                'latitude_in' => DB::raw('latitude'),
                'longitude_in' => DB::raw('longitude'),
            ]);

        // Optional: Drop old columns after migration
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old data if needed
        DB::table('attendances')
            ->whereNotNull('latitude_in')
            ->whereNotNull('longitude_in')
            ->update([
                'latitude' => DB::raw('latitude_in'),
                'longitude' => DB::raw('longitude_in'),
            ]);

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['latitude_in', 'longitude_in', 'latitude_out', 'longitude_out']);
        });
    }
};
