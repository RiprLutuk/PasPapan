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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->date('publish_date');
            $table->date('expire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['publish_date', 'expire_date', 'is_active'], 'idx_announcements_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
