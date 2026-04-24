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
        if (! Schema::hasColumn('attendances', 'approved_by')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Using foreignUlid because users uses ULID
                $table->foreignUlid('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approval_status');
            });
        }

        if (! Schema::hasColumn('reimbursements', 'approved_by')) {
            Schema::table('reimbursements', function (Blueprint $table) {
                $table->foreignUlid('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
        });
    }
};
