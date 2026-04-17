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
        Schema::table('company_assets', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('type');
            $table->decimal('purchase_cost', 15, 2)->nullable()->after('purchase_date');
            $table->date('expiration_date')->nullable()->after('purchase_cost');
        });

        // Safely alter ENUM using Raw SQL to avoid Doctrine/DBAL issues
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE company_assets MODIFY status ENUM('available', 'assigned', 'maintenance', 'lost', 'retired', 'sold', 'auctioned', 'disposed') DEFAULT 'available'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_assets', function (Blueprint $table) {
            $table->dropColumn(['purchase_date', 'purchase_cost', 'expiration_date']);
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE company_assets MODIFY status ENUM('available', 'assigned', 'maintenance', 'lost', 'retired') DEFAULT 'available'");
        }
    }
};
