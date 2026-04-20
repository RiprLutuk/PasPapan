<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'idx_reimbursements_user_date');
            $table->index(['status', 'created_at'], 'idx_reimbursements_status_created');
            $table->index(['status', 'updated_at'], 'idx_reimbursements_status_updated');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->index(['user_id', 'date', 'status'], 'idx_overtimes_user_date_status');
            $table->index(['user_id', 'date', 'start_time'], 'idx_overtimes_user_date_start');
            $table->index(['status', 'created_at'], 'idx_overtimes_status_created');
            $table->index(['status', 'updated_at'], 'idx_overtimes_status_updated');
        });

        Schema::table('cash_advances', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_cash_advances_user_status_created');
            $table->index(['status', 'created_at'], 'idx_cash_advances_status_created');
            $table->index(['status', 'updated_at'], 'idx_cash_advances_status_updated');
            $table->index(['payment_year', 'payment_month', 'status'], 'idx_cash_advances_payment_period_status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'year', 'month'], 'idx_payrolls_user_status_period');
            $table->index(['status', 'year', 'month'], 'idx_payrolls_status_period');
        });

        Schema::table('company_assets', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_company_assets_user_status_created');
            $table->index(['status', 'created_at'], 'idx_company_assets_status_created');
        });

        Schema::table('company_asset_histories', function (Blueprint $table) {
            $table->index(['user_id', 'action', 'date'], 'idx_asset_histories_user_action_date');
            $table->index(['company_asset_id', 'date'], 'idx_asset_histories_asset_date');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropIndex('idx_reimbursements_user_date');
            $table->dropIndex('idx_reimbursements_status_created');
            $table->dropIndex('idx_reimbursements_status_updated');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropIndex('idx_overtimes_user_date_status');
            $table->dropIndex('idx_overtimes_user_date_start');
            $table->dropIndex('idx_overtimes_status_created');
            $table->dropIndex('idx_overtimes_status_updated');
        });

        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropIndex('idx_cash_advances_user_status_created');
            $table->dropIndex('idx_cash_advances_status_created');
            $table->dropIndex('idx_cash_advances_status_updated');
            $table->dropIndex('idx_cash_advances_payment_period_status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payrolls_user_status_period');
            $table->dropIndex('idx_payrolls_status_period');
        });

        Schema::table('company_assets', function (Blueprint $table) {
            $table->dropIndex('idx_company_assets_user_status_created');
            $table->dropIndex('idx_company_assets_status_created');
        });

        Schema::table('company_asset_histories', function (Blueprint $table) {
            $table->dropIndex('idx_asset_histories_user_action_date');
            $table->dropIndex('idx_asset_histories_asset_date');
        });
    }
};
