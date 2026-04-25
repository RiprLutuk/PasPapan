<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\User;
use Livewire\Livewire;

test('employee page delete button renders valid livewire click binding', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create(['name' => 'Budi "Operator"']);

    $this->actingAs($superadmin);

    $response = $this->get(route('admin.employees'));

    $response->assertOk();
    $response->assertSee($employee->name);
});

test('employee component can open delete confirmation and delete user', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletion', $employee->id)
        ->assertSet('confirmingDeletion', true)
        ->assertSet('deleteName', $employee->name)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => $employee->id]);
});

test('employee component can approve a pending account deletion request', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create([
        'employment_status' => User::EMPLOYMENT_STATUS_DELETION_REQUESTED,
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'I already resigned from the company.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionApproval', $employee->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'approve')
        ->set('deletionReviewNotes', 'Approved by HR admin.')
        ->call('approveDeletionRequest')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->employment_status)->toBe(User::EMPLOYMENT_STATUS_DELETED)
        ->and($employee->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->account_deletion_review_notes)->toBe('Approved by HR admin.');
});

test('employee component can reject a pending account deletion request', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create([
        'employment_status' => User::EMPLOYMENT_STATUS_DELETION_REQUESTED,
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'Please delete my account.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletionRejection', $employee->id)
        ->assertSet('confirmingDeletionReview', true)
        ->assertSet('deletionReviewAction', 'reject')
        ->set('deletionReviewNotes', 'Employee still needs access for handover.')
        ->call('rejectDeletionRequest')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->employment_status)->toBe(User::EMPLOYMENT_STATUS_ACTIVE)
        ->and($employee->account_deletion_requested_at)->toBeNull()
        ->and($employee->account_deletion_reviewed_by)->toBe($superadmin->id)
        ->and($employee->account_deletion_review_notes)->toBe('Employee still needs access for handover.');
});
