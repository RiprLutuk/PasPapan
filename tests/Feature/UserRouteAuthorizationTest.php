<?php

use App\Models\CashAdvance;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

test('user self-service finance and overtime routes reject admin accounts', function (string $routeName) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'overtime',
    'my-kasbon',
]);

test('employee accounts can open self-service finance and overtime routes', function () {
    enableEnterpriseAttendanceForTests();

    $employee = User::factory()->create();

    $this->actingAs($employee)
        ->get(route('overtime'))
        ->assertOk();

    $this->actingAs($employee)
        ->get(route('my-kasbon'))
        ->assertOk();
});

test('self-service policies only expose overtime and cash advance indexes to employees', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($employee)->allows('viewAny', Overtime::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', Overtime::class))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('viewAny', CashAdvance::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', CashAdvance::class))->toBeFalse();
});

test('core self-service routes declare user or inbox-safe middleware coverage', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->getName());

    foreach (['home', 'my-schedule', 'face.enrollment'] as $routeName) {
        expect($routeCollection->get($routeName)?->gatherMiddleware())
            ->toContain('user');
    }

    expect($routeCollection->get('notifications')?->gatherMiddleware())
        ->toContain('auth:sanctum')
        ->toContain('verified');
});

test('admin accounts cannot open employee-only home schedule or face enrollment routes', function (string $routeName) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'home',
    'my-schedule',
    'face.enrollment',
]);

test('my schedule only renders schedules owned by the current user', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $ownShift = Shift::factory()->create(['name' => 'Visible Own Shift']);
    $otherShift = Shift::factory()->create(['name' => 'Hidden Coworker Shift']);

    Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $ownShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    Schedule::create([
        'user_id' => $otherEmployee->id,
        'shift_id' => $otherShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($employee)
        ->get(route('my-schedule'))
        ->assertOk()
        ->assertSee('Visible Own Shift')
        ->assertDontSee('Hidden Coworker Shift');
});

test('notifications page only renders notifications for the current user', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $employee->id,
        'data' => [
            'title' => 'Visible Employee Notification',
            'message' => 'This notification belongs to the signed in user.',
        ],
    ]);

    DatabaseNotification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherEmployee->id,
        'data' => [
            'title' => 'Hidden Coworker Notification',
            'message' => 'This notification belongs to another user.',
        ],
    ]);

    $this->actingAs($employee)
        ->get(route('notifications'))
        ->assertOk()
        ->assertSee('Visible Employee Notification')
        ->assertDontSee('Hidden Coworker Notification');
});
