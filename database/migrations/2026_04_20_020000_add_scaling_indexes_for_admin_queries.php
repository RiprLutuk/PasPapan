<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['date', 'approval_status', 'status'], 'idx_attendances_date_approval_status');
            $table->index(['date', 'time_out', 'user_id'], 'idx_attendances_date_time_out_user');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at', 'idx_activity_created_at');
            $table->index(['action', 'created_at'], 'idx_activity_action_created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notifications_notifiable_read_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['group', 'provinsi_kode'], 'idx_users_group_provinsi');
            $table->index(['group', 'kabupaten_kode'], 'idx_users_group_kabupaten');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_date_approval_status');
            $table->dropIndex('idx_attendances_date_time_out_user');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_created_at');
            $table->dropIndex('idx_activity_action_created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_notifiable_read_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_group_provinsi');
            $table->dropIndex('idx_users_group_kabupaten');
        });
    }
};
