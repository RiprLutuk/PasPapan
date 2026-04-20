<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_export_runs', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 64);
            $table->string('operation', 16);
            $table->string('status', 32)->default('queued');
            $table->foreignUlid('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('queue', 64)->nullable();
            $table->string('source_disk', 64)->nullable();
            $table->string('source_path')->nullable();
            $table->string('source_name')->nullable();
            $table->string('file_disk', 64)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows')->nullable();
            $table->json('meta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['resource', 'operation', 'status'], 'idx_import_export_runs_scope');
            $table->index(['requested_by_user_id', 'created_at'], 'idx_import_export_runs_requester_created');
            $table->index('status', 'idx_import_export_runs_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_export_runs');
    }
};
