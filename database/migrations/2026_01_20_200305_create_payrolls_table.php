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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->integer('month');
            $table->integer('year');

            // Financials
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->json('allowances')->nullable(); // e.g. [{"name": "Transport", "amount": 500000}]
            $table->json('deductions')->nullable(); // e.g. [{"name": "Tax", "amount": 200000}]
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, published, paid
            $table->foreignUlid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicate payrolls for same user/month
            $table->unique(['user_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
