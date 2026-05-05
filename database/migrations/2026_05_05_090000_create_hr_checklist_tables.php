<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('hr_checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hr_checklist_templates')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 64)->default('general');
            $table->string('default_assignee_type', 32)->default('hr');
            $table->integer('due_offset_days')->default(0);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'sort_order']);
        });

        Schema::create('hr_checklist_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hr_checklist_templates')->restrictOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32)->default('active');
            $table->date('effective_date');
            $table->foreignUlid('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'type']);
            $table->index('effective_date');
        });

        Schema::create('hr_checklist_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('hr_checklist_cases')->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('hr_checklist_template_items')->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 64)->default('general');
            $table->date('due_date')->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'status', 'due_date']);
            $table->index(['case_id', 'status']);
        });

        $this->grantSystemRolePermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_checklist_tasks');
        Schema::dropIfExists('hr_checklist_cases');
        Schema::dropIfExists('hr_checklist_template_items');
        Schema::dropIfExists('hr_checklist_templates');
    }

    private function grantSystemRolePermissions(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $permissions = ['admin.hr_checklists.view', 'admin.hr_checklists.manage'];

        DB::table('roles')
            ->whereIn('slug', ['super_admin', 'admin', 'hr'])
            ->orderBy('slug')
            ->get(['id', 'permissions'])
            ->each(function (object $role) use ($permissions): void {
                $existing = json_decode((string) $role->permissions, true);
                $existing = is_array($existing) ? $existing : [];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update([
                        'permissions' => json_encode(array_values(array_unique([...$existing, ...$permissions])), JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
            });
    }
};
