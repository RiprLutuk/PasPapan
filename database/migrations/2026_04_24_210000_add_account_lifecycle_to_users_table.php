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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employment_status')->default('active')->after('group');
            $table->timestamp('account_deletion_requested_at')->nullable()->after('employment_status');
            $table->text('account_deletion_reason')->nullable()->after('account_deletion_requested_at');
            $table->timestamp('account_deletion_reviewed_at')->nullable()->after('account_deletion_reason');
            $table->foreignUlid('account_deletion_reviewed_by')->nullable()->after('account_deletion_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('account_deletion_review_notes')->nullable()->after('account_deletion_reviewed_by');

            $table->index(['group', 'employment_status']);
            $table->index('account_deletion_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_deletion_reviewed_by');
            $table->dropIndex(['group', 'employment_status']);
            $table->dropIndex(['account_deletion_requested_at']);
            $table->dropColumn([
                'employment_status',
                'account_deletion_requested_at',
                'account_deletion_reason',
                'account_deletion_reviewed_at',
                'account_deletion_review_notes',
            ]);
        });
    }
};
