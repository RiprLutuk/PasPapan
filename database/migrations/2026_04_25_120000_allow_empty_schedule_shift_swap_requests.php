<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('shift_swap_requests', 'schedule_date')) {
                $table->date('schedule_date')->nullable()->after('schedule_id');
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('shift_swap_requests', function (Blueprint $table): void {
            $table->dropForeign(['schedule_id']);
            $table->foreignId('schedule_id')->nullable()->change();
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('shift_swap_requests', function (Blueprint $table): void {
                $table->dropForeign(['schedule_id']);
                $table->foreignId('schedule_id')->nullable(false)->change();
                $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            });
        }

        Schema::table('shift_swap_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('shift_swap_requests', 'schedule_date')) {
                $table->dropColumn('schedule_date');
            }
        });
    }
};
