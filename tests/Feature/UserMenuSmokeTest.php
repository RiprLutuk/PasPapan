<?php

use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Setting;
use App\Models\User;
use App\Services\Enterprise\LicenseGuard;

function seedUserMenuSmokeSettings(): void
{
    enableEnterpriseAttendanceForTests();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean', 'description' => 'Require Face ID enrollment before attendance']
    );

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean', 'description' => 'Require Face ID verification during attendance capture']
    );

    Setting::flushCache();
}

test('core user menu pages resolve cleanly for a regular user', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user);

    $routes = [
        'home',
        'apply-leave',
        'attendance-history',
        'scan',
        'notifications',
        'reimbursement',
        'my-schedule',
        'overtime',
        'face.enrollment',
        'profile.show',
        'my-payslips',
        'my-assets',
        'my-performance',
        'my-kasbon',
    ];

    foreach ($routes as $routeName) {
        $this->followingRedirects()
            ->get(route($routeName))
            ->assertOk();
    }
});

test('manager-only user menu pages resolve cleanly for a supervisor', function () {
    seedUserMenuSmokeSettings();

    $division = Division::create(['name' => 'Operations']);

    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);

    User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    $this->actingAs($manager);

    $routes = [
        'approvals',
        'approvals.history',
        'team-kasbon',
    ];

    foreach ($routes as $routeName) {
        $this->followingRedirects()
            ->get(route($routeName))
            ->assertOk();
    }
});

test('regular users keep a simplified shared navigation shell', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee(__('Toggle navigation menu'), false)
        ->assertDontSee(__('Open account menu'));
});

test('profile page exposes user preferences after navbar account menu removal', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee(__('Language'))
        ->assertSee(__('Appearance'));
});

test('admin navbar does not show language or theme toggles', function () {
    seedUserMenuSmokeSettings();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('language-toggle', false)
        ->assertDontSee('theme-switcher-desktop', false)
        ->assertDontSee('theme-switcher-mobile', false);
});

test('locked enterprise admin menu items remain visible with lock affordances', function () {
    seedUserMenuSmokeSettings();

    Setting::updateOrCreate(
        ['key' => 'enterprise_license_key'],
        ['value' => makeEnterpriseTestLicense(['features' => []]), 'group' => 'enterprise', 'type' => 'textarea']
    );
    Setting::flushCache('enterprise_license_key');
    LicenseGuard::clearLicenseCache();

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('Analytics Locked'))
        ->assertSee(__('Payroll Locked'))
        ->assertSee(__('Kasbon Locked'))
        ->assertSee(__('Settings Locked'))
        ->assertSee(__('Appraisals Locked'))
        ->assertSee(__('Asset Management Locked'))
        ->assertSee(__('KPI Settings Locked'))
        ->assertSee(__('Locked feature'));
});

test('admin profile page uses the admin profile route and shell', function () {
    seedUserMenuSmokeSettings();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.profile.show'))
        ->assertOk()
        ->assertSee(__('Admin Profile'))
        ->assertSee(route('admin.profile.show'), false)
        ->assertDontSee('href="'.route('profile.show').'"', false);
});

test('admin users are redirected from the user profile page to the admin profile page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('profile.show'))
        ->assertRedirect(route('admin.profile.show'));
});
