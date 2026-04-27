<?php

use App\Models\CashAdvance;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
