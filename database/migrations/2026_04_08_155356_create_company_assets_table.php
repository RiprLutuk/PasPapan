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
        Schema::create('company_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('type')->default('electronics'); // electronics, vehicle, furniture, uniform
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_assigned')->nullable();
            $table->date('return_date')->nullable();
            $table->enum('status', ['available', 'assigned', 'maintenance', 'lost', 'retired'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_assets');
    }
};
