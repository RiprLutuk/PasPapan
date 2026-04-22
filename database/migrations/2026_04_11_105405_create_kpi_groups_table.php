<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kpi_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('weight')->default(0); // Group weight percentage
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add group foreign key to kpi_templates
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->foreignId('kpi_group_id')->nullable()->after('id')->constrained('kpi_groups')->nullOnDelete();
        });

        // Migrate existing flat KPIs into a default "Legacy" group
        $existingKpis = \App\Models\KpiTemplate::all();
        if ($existingKpis->count() > 0) {
            $group = DB::table('kpi_groups')->insertGetId([
                'name' => 'Indikator Kinerja Utama',
                'weight' => 100,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('kpi_templates')->whereNull('kpi_group_id')->update(['kpi_group_id' => $group]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->dropForeign(['kpi_group_id']);
            $table->dropColumn('kpi_group_id');
        });
        Schema::dropIfExists('kpi_groups');
    }
};
