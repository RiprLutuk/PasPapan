<?php

use App\Livewire\Admin\MasterData\LeaveTypeManager;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('admin and hr roles can manage leave types', function () {
    $admin = User::factory()->admin()->create();
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
    $hrRole = Role::query()->where('slug', 'hr')->firstOrFail();

    $admin->roles()->sync([$adminRole->id]);
    $hr->roles()->sync([$hrRole->id]);

    expect(Gate::forUser($admin)->allows('manageLeaveTypes'))->toBeTrue()
        ->and(Gate::forUser($hr)->allows('manageLeaveTypes'))->toBeTrue()
        ->and(Gate::forUser($employee)->allows('manageLeaveTypes'))->toBeFalse();
});

test('leave type manager can create custom leave type and prevents sick quota usage', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(LeaveTypeManager::class)
        ->call('showCreating')
        ->set('name', 'Cuti Menikah')
        ->set('description', 'Cuti khusus untuk pernikahan karyawan.')
        ->set('category', LeaveType::CATEGORY_OTHER)
        ->set('counts_against_quota', false)
        ->set('requires_attachment', true)
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leave_types', [
        'name' => 'Cuti Menikah',
        'category' => LeaveType::CATEGORY_OTHER,
        'counts_against_quota' => false,
        'requires_attachment' => true,
    ]);

    $sickLeave = LeaveType::query()->where('code', 'sick_leave')->firstOrFail();

    Livewire::test(LeaveTypeManager::class)
        ->call('edit', $sickLeave->id)
        ->set('counts_against_quota', true)
        ->call('update')
        ->assertHasNoErrors();

    expect($sickLeave->refresh()->counts_against_quota)->toBeFalse();
});
