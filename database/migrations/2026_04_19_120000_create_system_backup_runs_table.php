<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_backup_runs')) {
            $this->repairExistingTable();

            return;
        }

        Schema::create('system_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('status', 32)->index();
            $table->foreignUlid('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('queue', 64)->nullable();
            $table->string('file_disk', 32)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_runs');
    }

    private function repairExistingTable(): void
    {
        if (! Schema::hasColumn('system_backup_runs', 'requested_by_user_id')) {
            Schema::table('system_backup_runs', function (Blueprint $table) {
                $table->char('requested_by_user_id', 26)->nullable()->after('status');
            });
        }

        try {
            DB::statement('ALTER TABLE `system_backup_runs` DROP FOREIGN KEY `system_backup_runs_requested_by_user_id_foreign`');
        } catch (\Throwable $e) {
            // Ignore missing constraints from partially applied migrations.
        }

        try {
            DB::statement('ALTER TABLE `system_backup_runs` MODIFY `requested_by_user_id` CHAR(26) NULL');
        } catch (\Throwable $e) {
            // Ignore unsupported change attempts when the column is already compatible.
        }

        try {
            DB::statement('ALTER TABLE `system_backup_runs` ADD CONSTRAINT `system_backup_runs_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Ignore duplicate constraints when the repair already succeeded.
        }
    }
};
