<?php

use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

function seedUserApprovalCoverageSettings(): void
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

function createApprovalCoverageManager(): array
{
    $division = Division::create(['name' => 'Operations']);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);

    $subordinate = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    return [$manager, $subordinate];
}

test('manager-only user routes declare explicit subordinate review middleware', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->uri());

    foreach ([
        'approvals',
        'approvals/history',
        'team-kasbon',
    ] as $uri) {
        $route = $routeCollection->get($uri);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('can:reviewSubordinateRequests');
    }
});

test('subordinate review gate only allows users with subordinates', function () {
    [$manager] = createApprovalCoverageManager();
    $regularUser = User::factory()->create([
        'division_id' => null,
        'job_title_id' => null,
    ]);

    expect(Gate::forUser($manager)->allows('reviewSubordinateRequests'))->toBeTrue()
        ->and(Gate::forUser($regularUser)->allows('reviewSubordinateRequests'))->toBeFalse();
});

test('manager-only user pages reject users without subordinate review access', function () {
    seedUserApprovalCoverageSettings();

    $regularUser = User::factory()->create([
        'division_id' => null,
        'job_title_id' => null,
    ]);

    $this->actingAs($regularUser)
        ->get(route('approvals'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('approvals.history'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('team-kasbon'))
        ->assertForbidden();
});

test('manager-only user shortcuts are hidden unless subordinate review access exists', function () {
    seedUserApprovalCoverageSettings();

    [$manager] = createApprovalCoverageManager();
    $regularUser = User::factory()->create([
        'division_id' => null,
        'job_title_id' => null,
    ]);

    $this->actingAs($regularUser)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText(__('Approvals'))
        ->assertDontSeeText(__('Team Kasbon'));

    $this->actingAs($manager)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeText(__('Approvals'))
        ->assertSeeText(__('Team Kasbon'));
});
