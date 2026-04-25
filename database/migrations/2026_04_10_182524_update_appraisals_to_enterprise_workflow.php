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
        // Wipe existing legacy appraisal data to prevent structural conflicts
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::table('appraisals')->delete();
        } else {
            \Illuminate\Support\Facades\DB::table('appraisals')->truncate();
        }

        // 1. Create KPI Settings Table
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('weight')->default(0)->comment('Percentage weight out of 100');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Extend Current Appraisals Table
        Schema::table('appraisals', function (Blueprint $table) {
            $table->date('meeting_date')->nullable()->after('final_score');
            $table->string('meeting_link')->nullable()->after('meeting_date');
            $table->boolean('employee_acknowledgement')->default(false)->after('notes');
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            Schema::table('appraisals', function (Blueprint $table) {
                $table->enum('status', ['draft', 'self_assessment', 'manager_review', '1on1_scheduled', 'completed'])
                    ->default('draft')
                    ->after('period_year');
            });
        } else {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE appraisals MODIFY evaluator_id CHAR(26) NULL');
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE appraisals ADD COLUMN status ENUM('draft', 'self_assessment', 'manager_review', '1on1_scheduled', 'completed') DEFAULT 'draft' AFTER period_year");
        }

        // 3. Create Evaluation Mapping Table
        Schema::create('appraisal_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('kpi_template_id')->constrained('kpi_templates')->cascadeOnDelete();
            $table->integer('self_score')->nullable()->comment('1-100 scale');
            $table->integer('manager_score')->nullable()->comment('1-100 scale');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['appraisal_id', 'kpi_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_evaluations');

        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn(['status', 'meeting_date', 'meeting_link', 'employee_acknowledgement']);
        });

        Schema::dropIfExists('kpi_templates');
    }
};
