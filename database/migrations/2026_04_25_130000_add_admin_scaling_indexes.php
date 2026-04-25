<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['group', 'name'], 'idx_users_group_name');
            $table->index(['group', 'division_id', 'name'], 'idx_users_group_division_name');
            $table->index(['group', 'job_title_id', 'name'], 'idx_users_group_job_title_name');
            $table->index(['group', 'education_id', 'name'], 'idx_users_group_education_name');
            $table->index(['group', 'employment_status', 'name'], 'idx_users_group_employment_name');
            $table->index(['group', 'kabupaten_kode', 'name'], 'idx_users_group_kabupaten_name');
            $table->index(['group', 'provinsi_kode', 'name'], 'idx_users_group_provinsi_name');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->index(['date', 'user_id', 'status'], 'idx_attendances_date_user_status');
            $table->index(['approval_status', 'user_id', 'date'], 'idx_attendances_approval_user_date');
            $table->index(['approval_status', 'status', 'date', 'user_id'], 'idx_attendances_leave_approval_grid');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->index(['status', 'request_type', 'attendance_date', 'created_at'], 'idx_att_corr_status_type_date_created');
            $table->index(['user_id', 'status', 'updated_at'], 'idx_att_corr_user_status_updated');
        });

        Schema::table('shift_swap_requests', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'idx_shift_swaps_status_created');
            $table->index(['status', 'updated_at'], 'idx_shift_swaps_status_updated');
            $table->index(['user_id', 'status', 'schedule_date'], 'idx_shift_swaps_user_status_date');
        });

        Schema::table('employee_document_requests', function (Blueprint $table): void {
            $table->index(['status', 'document_type', 'created_at'], 'idx_doc_requests_status_type_created');
            $table->index(['user_id', 'status', 'created_at'], 'idx_doc_requests_user_status_created');
        });

        Schema::table('reimbursements', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'created_at'], 'idx_reimbursements_user_status_created');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->index(['year', 'month', 'status'], 'idx_payrolls_period_status');
        });

        Schema::table('company_assets', function (Blueprint $table): void {
            $table->index(['type', 'created_at'], 'idx_company_assets_type_created');
            $table->index(['user_id', 'created_at'], 'idx_company_assets_user_created');
        });
    }

    public function down(): void
    {
        Schema::table('company_assets', function (Blueprint $table): void {
            $table->dropIndex('idx_company_assets_type_created');
            $table->dropIndex('idx_company_assets_user_created');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropIndex('idx_payrolls_period_status');
        });

        Schema::table('reimbursements', function (Blueprint $table): void {
            $table->dropIndex('idx_reimbursements_user_status_created');
        });

        Schema::table('employee_document_requests', function (Blueprint $table): void {
            $table->dropIndex('idx_doc_requests_status_type_created');
            $table->dropIndex('idx_doc_requests_user_status_created');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropIndex('idx_att_corr_status_type_date_created');
            $table->dropIndex('idx_att_corr_user_status_updated');
        });

        Schema::table('shift_swap_requests', function (Blueprint $table): void {
            $table->dropIndex('idx_shift_swaps_status_created');
            $table->dropIndex('idx_shift_swaps_status_updated');
            $table->dropIndex('idx_shift_swaps_user_status_date');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('idx_attendances_leave_approval_grid');
            $table->dropIndex('idx_attendances_date_user_status');
            $table->dropIndex('idx_attendances_approval_user_date');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('idx_users_group_name');
            $table->dropIndex('idx_users_group_division_name');
            $table->dropIndex('idx_users_group_job_title_name');
            $table->dropIndex('idx_users_group_education_name');
            $table->dropIndex('idx_users_group_employment_name');
            $table->dropIndex('idx_users_group_kabupaten_name');
            $table->dropIndex('idx_users_group_provinsi_name');
        });
    }
};
