<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'security.rate_limit_global',
                'value' => '1000',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Global API rate limit per minute',
            ],
            [
                'key' => 'security.rate_limit_login',
                'value' => '5',
                'group' => 'security',
                'type' => 'number',
                'description' => 'Login rate limit per minute',
            ],
            [
                'key' => 'attendance.grace_period',
                'value' => '10',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Late Grace Period (minutes)',
            ],
            [
                'key' => 'app.name',
                'value' => env('APP_NAME', 'PasPapan'),
                'group' => 'identity',
                'type' => 'text',
                'description' => 'Application Name',
            ],
            [
                'key' => 'app.company_name',
                'value' => 'PT. PasPapan Indonesia',
                'group' => 'identity',
                'type' => 'text',
                'description' => 'Company Name for Reports',
            ],
            [
                'key' => 'app.support_contact',
                'value' => 'example@gmail.com',
                'group' => 'identity',
                'type' => 'text',
                'description' => 'Support Email/Phone',
            ],
            [
                'key' => 'feature.require_photo',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Require Photo for Attendance',
            ],
            [
                'key' => 'app.maintenance_mode',
                'value' => '0',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Enable Maintenance Mode',
            ],
            [
                'key' => 'app.time_format',
                'value' => '24',
                'group' => 'general',
                'type' => 'select',
                'description' => 'Time Format (12h/24h)',
            ],
            [
                'key' => 'app.show_seconds',
                'value' => '0',
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Show Seconds in Time Display',
            ],
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
                'value' => '0',
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
            [
                'key' => 'notif.admin_email',
                'value' => 'example@gmail.com',
                'group' => 'notification',
                'type' => 'text',
                'description' => 'Email Admin untuk Notifikasi (kosongkan jika tidak ada)',
            ],
            [
                'key' => 'attendance.work_hours_per_day',
                'value' => '8',
                'group' => 'attendance',
                'type' => 'number',
                'description' => 'Jam Kerja per Hari',
            ],
            [
                'key' => 'app.company_address',
                'value' => 'Jalan example, example, example, example, example',
                'group' => 'identity',
                'type' => 'textarea',
                'description' => 'Company Address',
            ],
            [
                'key' => 'enterprise_license_key',
                'value' => '',
                'group' => 'enterprise',
                'type' => 'textarea',
                'description' => 'Enterprise License Key',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
