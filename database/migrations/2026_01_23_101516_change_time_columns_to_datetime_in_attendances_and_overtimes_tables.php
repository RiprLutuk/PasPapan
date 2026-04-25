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
        $driver = DB::getDriverName();

        // 1. Add temporary datetime columns
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('time_in_dt')->nullable()->after('time_in');
            $table->dateTime('time_out_dt')->nullable()->after('time_out');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dateTime('start_time_dt')->nullable()->after('start_time');
            $table->dateTime('end_time_dt')->nullable()->after('end_time');
        });

        // 2. Migrate Data (Best Effort)
        // Combine date + time. For time_out < time_in, we assume next day?
        // For simplicity in this patch, we combine date + time.
        // If the user has valid dates, this works for same-day.
        // For cross-day, the old data was likely broken anyway (negative duration),
        // so setting it to date+time is at least a start.

        // SQLite/MySQL compatible raw statements might differ, assuming MySQL/MariaDB for production,
        // but using Laravel query builder for safety where possible.

        // ATTENDANCES
        if ($driver === 'sqlite') {
            DB::statement("UPDATE attendances SET time_in_dt = date || ' ' || time_in WHERE time_in IS NOT NULL");
            DB::statement("UPDATE attendances SET time_out_dt = date || ' ' || time_out WHERE time_out IS NOT NULL");
            DB::statement("UPDATE attendances SET time_out_dt = datetime(time_out_dt, '+1 day') WHERE time_out_dt < time_in_dt");

            DB::statement("UPDATE overtimes SET start_time_dt = date || ' ' || start_time WHERE start_time IS NOT NULL");
            DB::statement("UPDATE overtimes SET end_time_dt = date || ' ' || end_time WHERE end_time IS NOT NULL");
            DB::statement("UPDATE overtimes SET end_time_dt = datetime(end_time_dt, '+1 day') WHERE end_time_dt < start_time_dt");
        } else {
            DB::statement("UPDATE attendances SET time_in_dt = CONCAT(date, ' ', time_in) WHERE time_in IS NOT NULL");
            DB::statement("UPDATE attendances SET time_out_dt = CONCAT(date, ' ', time_out) WHERE time_out IS NOT NULL");
            DB::statement('UPDATE attendances SET time_out_dt = DATE_ADD(time_out_dt, INTERVAL 1 DAY) WHERE time_out_dt < time_in_dt');

            DB::statement("UPDATE overtimes SET start_time_dt = CONCAT(date, ' ', start_time) WHERE start_time IS NOT NULL");
            DB::statement("UPDATE overtimes SET end_time_dt = CONCAT(date, ' ', end_time) WHERE end_time IS NOT NULL");
            DB::statement('UPDATE overtimes SET end_time_dt = DATE_ADD(end_time_dt, INTERVAL 1 DAY) WHERE end_time_dt < start_time_dt');
        }

        // 3. Drop old columns and rename new ones
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['time_in', 'time_out']);
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('time_in_dt', 'time_in');
            $table->renameColumn('time_out_dt', 'time_out');
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->renameColumn('start_time_dt', 'start_time');
            $table->renameColumn('end_time_dt', 'end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this is complex (lossy). We will just convert back to time only.

        Schema::table('attendances', function (Blueprint $table) {
            $table->time('time_in_old')->nullable();
            $table->time('time_out_old')->nullable();
        });

        DB::statement('UPDATE attendances SET time_in_old = TIME(time_in), time_out_old = TIME(time_out)');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['time_in', 'time_out']);
            $table->renameColumn('time_in_old', 'time_in');
            $table->renameColumn('time_out_old', 'time_out');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('start_time_old')->nullable();
            $table->time('end_time_old')->nullable();
        });

        DB::statement('UPDATE overtimes SET start_time_old = TIME(start_time), end_time_old = TIME(end_time)');

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
            $table->renameColumn('start_time_old', 'start_time');
            $table->renameColumn('end_time_old', 'end_time');
        });
    }
};
