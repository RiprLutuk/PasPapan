<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->timestamp('payslip_password_requested_at')->nullable()->after('paid_at');
            $table->timestamp('pdf_emailed_at')->nullable()->after('payslip_password_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropColumn(['payslip_password_requested_at', 'pdf_emailed_at']);
        });
    }
};
