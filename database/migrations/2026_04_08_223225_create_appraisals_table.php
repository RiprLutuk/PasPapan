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
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('period_month');
            $table->year('period_year');
            $table->decimal('attendance_score', 5, 2)->default(0)->comment('Calculated automatically');
            $table->decimal('subjective_score', 5, 2)->default(0)->comment('Given by the evaluator');
            $table->decimal('final_score', 5, 2)->default(0)->comment('Average of attendance + subjective');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure unique appraisal per user per period
            $table->unique(['user_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
