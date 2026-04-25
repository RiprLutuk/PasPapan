<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // Leave/Cuti Management
            [
                'key' => 'leave.annual_quota',
                'value' => '12',
                'group' => 'leave',
                'type' => 'number',
                'description' => 'Jatah Cuti Tahunan (hari)',
            ],
            [
                'key' => 'leave.sick_quota',
                'value' => '14',
                'group' => 'leave',
                'type' => 'number',
                'description' => 'Jatah Sakit per Tahun (hari)',
            ],
            [
                'key' => 'leave.require_attachment',
                'value' => '1',
                'group' => 'leave',
                'type' => 'boolean',
                'description' => 'Wajib Lampiran untuk Pengajuan Cuti/Sakit',
            ],
            [
                'key' => 'leave.auto_approve_days',
                'value' => '3',
                'group' => 'leave',
                'type' => 'number',
                'description' => 'Auto-Approve jika tidak diproses dalam X hari (0 = disabled)',
            ],

            // Notification
            [
                'key' => 'notif.admin_email',
                'value' => '',
                'group' => 'notification',
                'type' => 'text',
                'description' => 'Email Admin untuk Notifikasi (kosongkan jika tidak ada)',
            ],

            // Attendance
            [
                'key' => 'attendance.work_hours_per_day',
                'value' => '8',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Jam Kerja per Hari',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'leave.annual_quota',
            'leave.sick_quota',
            'leave.require_attachment',
            'leave.auto_approve_days',
            'notif.admin_email',
            'attendance.work_hours_per_day',
        ])->delete();
    }
};
