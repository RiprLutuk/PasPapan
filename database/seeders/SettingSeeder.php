<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Security Group
        Setting::updateOrCreate(['key' => 'security.rate_limit_global'], [
            'value' => '1000',
            'type' => 'number',
            'group' => 'security',
            'description' => 'Global API rate limit per minute'
        ]);

        Setting::updateOrCreate(['key' => 'security.rate_limit_login'], [
            'value' => '5',
            'type' => 'number',
            'group' => 'security',
            'description' => 'Login rate limit per minute'
        ]);

        // Attendance Group


        Setting::updateOrCreate(['key' => 'attendance.grace_period'], [
            'value' => '15',
            'type' => 'number',
            'group' => 'attendance',
            'description' => 'Late Grace Period (minutes)'
        ]);

        // App Identity Group
        Setting::updateOrCreate(['key' => 'app.name'], [
            'value' => 'PasPapan',
            'type' => 'text',
            'group' => 'identity',
            'description' => 'Application Name'
        ]);

        Setting::updateOrCreate(['key' => 'app.company_name'], [
            'value' => 'My Company',
            'type' => 'text',
            'group' => 'identity',
            'description' => 'Company Name for Reports'
        ]);

        Setting::updateOrCreate(['key' => 'app.support_contact'], [
            'value' => 'admin@example.com',
            'type' => 'text',
            'group' => 'identity',
            'description' => 'Support Email/Phone'
        ]);

        // Features Group
        Setting::updateOrCreate(['key' => 'feature.require_photo'], [
            'value' => '1', // true
            'type' => 'boolean',
            'group' => 'features',
            'description' => 'Require Photo for Attendance'
        ]);

        Setting::updateOrCreate(['key' => 'app.maintenance_mode'], [
            'value' => '0', // false
            'type' => 'boolean',
            'group' => 'features',
            'description' => 'Enable Maintenance Mode'
        ]);
        Setting::updateOrCreate(['key' => 'app.time_format'], [
            'value' => '24', // 12 or 24
            'type' => 'select',
            // Options handled in view for now
            'group' => 'general',
            'description' => 'Time Format (12h/24h)'
        ]);

        Setting::updateOrCreate(['key' => 'app.show_seconds'], [
            'value' => '0', // false
            'type' => 'boolean',
            'group' => 'general',
            'description' => 'Show Seconds in Time Display'
        ]);
    }
}
