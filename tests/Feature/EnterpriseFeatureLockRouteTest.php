<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    LicenseGuard::clearLicenseCache();

    Setting::updateOrCreate(
        ['key' => 'enterprise_license_key'],
        ['value' => makeEnterpriseTestLicense(['features' => []]), 'group' => 'enterprise', 'type' => 'textarea']
    );
    Setting::flushCache('enterprise_license_key');
});

test('locked enterprise admin routes show upgrade modal for authorized admins', function (string $routeName, string $title) {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin)
        ->get(route($routeName))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('show-feature-lock', fn (array $payload): bool => $payload['title'] === __($title));
})->with([
    ['admin.analytics', 'Analytics Locked'],
    ['admin.payrolls', 'Payroll Locked'],
    ['admin.payroll.settings', 'Payroll Locked'],
    ['admin.manage-kasbon', 'Kasbon Locked'],
    ['admin.assets', 'Asset Management Locked'],
    ['admin.appraisals', 'Appraisals Locked'],
    ['admin.settings.kpi', 'Appraisals Locked'],
    ['admin.import-export.users', 'Import/Export Locked'],
    ['admin.import-export.attendances', 'Import/Export Locked'],
    ['admin.activity-logs', 'Audit Export Locked'],
]);

test('locked enterprise admin routes still forbid admins without the underlying permission', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.analytics'))
        ->assertForbidden();
});

test('locked enterprise user routes show upgrade modal for normal users', function (string $routeName, string $title) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('home'))
        ->assertSessionHas('show-feature-lock', fn (array $payload): bool => $payload['title'] === __($title));
})->with([
    ['my-payslips', 'Payroll Locked'],
    ['my-kasbon', 'Kasbon Locked'],
    ['my-assets', 'Asset Management Locked'],
    ['my-performance', 'Appraisals Locked'],
]);
