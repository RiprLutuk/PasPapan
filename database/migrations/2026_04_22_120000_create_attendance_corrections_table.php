<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('attendance_date');
            $table->string('request_type', 32);
            $table->dateTime('requested_time_in')->nullable();
            $table->dateTime('requested_time_out')->nullable();
            $table->foreignId('requested_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->json('current_snapshot')->nullable();
            $table->text('reason');
            $table->string('status', 32)->default('pending');
            $table->foreignUlid('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'attendance_date'], 'idx_attendance_corrections_status_date');
            $table->index(['user_id', 'attendance_date'], 'idx_attendance_corrections_user_date');
            $table->index(['request_type', 'status'], 'idx_attendance_corrections_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
