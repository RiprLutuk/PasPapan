<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('other');
            $table->boolean('counts_against_quota')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        foreach ($this->defaultTypes() as $index => $type) {
            DB::table('leave_types')->insert([
                ...$type,
                'sort_order' => ($index + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasColumn('attendances', 'leave_type_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('leave_type_id')
                    ->nullable()
                    ->constrained('leave_types')
                    ->nullOnDelete();

                $table->index(['leave_type_id', 'approval_status', 'date'], 'idx_attendances_leave_type_approval_date');
            });
        }

        $annualLeaveId = DB::table('leave_types')->where('code', 'annual_leave')->value('id');
        $sickLeaveId = DB::table('leave_types')->where('code', 'sick_leave')->value('id');

        if ($annualLeaveId) {
            DB::table('attendances')
                ->whereNull('leave_type_id')
                ->whereIn('status', ['excused', 'leave', 'permission'])
                ->update(['leave_type_id' => $annualLeaveId]);
        }

        if ($sickLeaveId) {
            DB::table('attendances')
                ->whereNull('leave_type_id')
                ->where('status', 'sick')
                ->update(['leave_type_id' => $sickLeaveId]);
        }

        DB::table('settings')->where('key', 'leave.sick_quota')->delete();
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'leave_type_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendances_leave_type_approval_date');
                $table->dropConstrainedForeignId('leave_type_id');
            });
        }

        Schema::dropIfExists('leave_types');

        DB::table('settings')->updateOrInsert(
            ['key' => 'leave.sick_quota'],
            [
                'value' => '14',
                'group' => 'leave',
                'type' => 'number',
                'description' => 'Jatah Sakit per Tahun (hari)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function defaultTypes(): array
    {
        return [
            [
                'code' => 'annual_leave',
                'name' => 'Cuti Tahunan',
                'description' => 'Cuti tahunan yang mengurangi kuota tahunan.',
                'category' => 'annual',
                'counts_against_quota' => true,
                'requires_attachment' => false,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'sick_leave',
                'name' => 'Cuti Sakit',
                'description' => 'Cuti sakit tanpa pemotongan kuota tahunan.',
                'category' => 'sick',
                'counts_against_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'maternity_leave',
                'name' => 'Cuti Melahirkan',
                'description' => 'Cuti melahirkan atau pendampingan kelahiran sesuai kebijakan perusahaan.',
                'category' => 'other',
                'counts_against_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'is_system' => false,
            ],
            [
                'code' => 'umrah_leave',
                'name' => 'Cuti Umroh',
                'description' => 'Cuti ibadah umroh sesuai persetujuan perusahaan.',
                'category' => 'other',
                'counts_against_quota' => false,
                'requires_attachment' => true,
                'is_active' => true,
                'is_system' => false,
            ],
            [
                'code' => 'personal_permission',
                'name' => 'Izin Pribadi',
                'description' => 'Izin non-sakit yang tidak mengurangi kuota cuti tahunan.',
                'category' => 'other',
                'counts_against_quota' => false,
                'requires_attachment' => false,
                'is_active' => true,
                'is_system' => false,
            ],
        ];
    }
};
