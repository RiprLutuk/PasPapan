<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // GPS Accuracy columns (in meters)
            $table->decimal('accuracy_in', 10, 2)->nullable()->after('longitude_in');
            $table->decimal('accuracy_out', 10, 2)->nullable()->after('longitude_out');

            // GPS Variance (standard deviation of multiple samples)
            $table->decimal('gps_variance_in', 12, 8)->nullable()->after('accuracy_in');
            $table->decimal('gps_variance_out', 12, 8)->nullable()->after('accuracy_out');

            // Suspicious flag
            $table->boolean('is_suspicious')->default(false)->after('gps_variance_out');
            $table->string('suspicious_reason')->nullable()->after('is_suspicious');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'accuracy_in',
                'accuracy_out',
                'gps_variance_in',
                'gps_variance_out',
                'is_suspicious',
                'suspicious_reason',
            ]);
        });
    }
};
