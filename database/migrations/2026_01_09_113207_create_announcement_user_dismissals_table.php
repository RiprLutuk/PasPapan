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
        Schema::create('announcement_user_dismissals', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 26); // Match users table ID type (ULID)
            $table->foreignId('announcement_id')->constrained()->onDelete('cascade');
            $table->timestamp('dismissed_at')->useCurrent();
            
            $table->unique(['user_id', 'announcement_id']);
            $table->index('user_id');
            
            // Add foreign key manually
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_user_dismissals');
    }
};
