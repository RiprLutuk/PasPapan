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
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->text('indicator_description')->nullable()->after('name');
        });

        Schema::table('appraisal_evaluations', function (Blueprint $table) {
            $table->text('evidence_description')->nullable()->after('kpi_template_id');
        });

        Schema::table('appraisals', function (Blueprint $table) {
            $table->text('employee_notes')->nullable()->after('notes');
            $table->text('development_recommendation')->nullable()->after('employee_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn(['employee_notes', 'development_recommendation']);
        });

        Schema::table('appraisal_evaluations', function (Blueprint $table) {
            $table->dropColumn('evidence_description');
        });

        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->dropColumn('indicator_description');
        });
    }
};
