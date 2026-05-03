<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_document_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_document_templates', 'layout_options')) {
                $table->json('layout_options')->nullable()->after('footer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_document_templates', function (Blueprint $table) {
            if (Schema::hasColumn('employee_document_templates', 'layout_options')) {
                $table->dropColumn('layout_options');
            }
        });
    }
};
