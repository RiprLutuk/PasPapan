<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('category', 64)->default('hr');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('employee_requestable')->default(true);
            $table->boolean('admin_requestable')->default(true);
            $table->boolean('requires_employee_upload')->default(false);
            $table->boolean('auto_generate_enabled')->default(false);
            $table->json('allowed_requester_groups')->nullable();
            $table->json('allowed_reviewer_groups')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });

        Schema::create('employee_document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('employee_document_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('paper_size', 16)->default('a4');
            $table->string('orientation', 16)->default('portrait');
            $table->longText('body');
            $table->text('footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_type_id', 'is_active']);
        });

        Schema::table('employee_document_requests', function (Blueprint $table) {
            $table->foreignId('document_type_id')
                ->nullable()
                ->after('user_id')
                ->constrained('employee_document_types')
                ->nullOnDelete();
            $table->foreignUlid('requested_by')->nullable()->after('document_type')->constrained('users')->nullOnDelete();
            $table->string('request_source', 32)->default('employee')->after('requested_by');
            $table->date('due_date')->nullable()->after('details');
            $table->string('uploaded_path')->nullable()->after('due_date');
            $table->string('uploaded_original_name')->nullable()->after('uploaded_path');
            $table->timestamp('uploaded_at')->nullable()->after('uploaded_original_name');
            $table->string('generated_path')->nullable()->after('uploaded_at');
            $table->foreignId('generated_template_id')
                ->nullable()
                ->after('generated_path')
                ->constrained('employee_document_templates')
                ->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('generated_template_id');
            $table->json('metadata')->nullable()->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropConstrainedForeignId('generated_template_id');
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn([
                'request_source',
                'due_date',
                'uploaded_path',
                'uploaded_original_name',
                'uploaded_at',
                'generated_path',
                'generated_at',
                'metadata',
            ]);
        });

        Schema::dropIfExists('employee_document_templates');
        Schema::dropIfExists('employee_document_types');
    }
};
