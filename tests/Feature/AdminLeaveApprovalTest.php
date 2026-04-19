<?php

use App\Livewire\Admin\LeaveApproval;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('admin leave approvals show all request statuses by default', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create(['name' => 'Leave Request Employee']);

    Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'leave',
        'approval_status' => Attendance::STATUS_APPROVED,
        'note' => 'Approved family leave',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSet('statusFilter', 'all')
        ->assertSee('Leave Request Employee')
        ->assertSee('Approved family leave');
});

test('admin leave approvals are not hidden by regional employee scope', function () {
    $admin = User::factory()->admin()->create([
        'provinsi_kode' => '11',
        'kabupaten_kode' => '11.01',
    ]);
    $employee = User::factory()->create([
        'name' => 'Different Region Leave Employee',
        'provinsi_kode' => '12',
        'kabupaten_kode' => '12.01',
    ]);

    Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'sick',
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Sick leave from another region',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->assertSee('Different Region Leave Employee')
        ->assertSee('Sick leave from another region');
});

test('rejecting leave keeps request type visible under rejected approval filter', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create(['name' => 'Rejected Leave Employee']);
    $attendance = Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'leave',
        'approval_status' => Attendance::STATUS_PENDING,
        'note' => 'Need leave for permit',
    ]);

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->call('confirmReject', [$attendance->id])
        ->set('rejectionNote', 'Permit quota is full')
        ->call('reject')
        ->assertDispatched('saved');

    $attendance->refresh();

    expect($attendance->status)->toBe('leave')
        ->and($attendance->approval_status)->toBe(Attendance::STATUS_REJECTED)
        ->and($attendance->rejection_note)->toBe('Permit quota is full');

    Livewire::actingAs($admin)
        ->test(LeaveApproval::class)
        ->set('statusFilter', Attendance::STATUS_REJECTED)
        ->assertSee('Rejected Leave Employee')
        ->assertSee('Permit quota is full');
});
