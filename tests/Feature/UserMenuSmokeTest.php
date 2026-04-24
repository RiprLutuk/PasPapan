<?php

use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Setting;
use App\Models\User;

function seedUserMenuSmokeSettings(): void
{
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

test('regular users still get account controls in the shared navigation shell', function () {
    seedUserMenuSmokeSettings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('profile.show'), false)
        ->assertSee(__('Toggle navigation menu'), false)
        ->assertSee(__('Log Out'));
});
