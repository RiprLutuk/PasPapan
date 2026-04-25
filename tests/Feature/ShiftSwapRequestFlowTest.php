<?php

use App\Livewire\Admin\ShiftSwapApprovalManager;
use App\Livewire\User\ShiftSwapRequestPage;
use App\Livewire\User\TeamApprovals;
use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\TeamApprovalQueryService;
use Livewire\Livewire;

function createShiftSwapApprovalHierarchy(): array
{
    $division = Division::create(['name' => 'Store Operations']);
    $managerLevel = JobLevel::create(['name' => 'Supervisor', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Crew', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Store Supervisor',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Store Crew',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);

    $employee = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    $replacement = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    return [$manager, $employee, $replacement];
}

test('employee submits a shift swap request for an upcoming schedule', function () {
    [, $employee, $replacement] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleDate', $schedule->date->toDateString())
        ->set('requestedShiftId', $requestedShift->id)
        ->set('replacementUserId', $replacement->id)
        ->set('reason', 'Need to cover a family appointment in the morning.')
        ->call('store')
        ->assertHasNoErrors();

    $request = ShiftSwapRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($employee->id)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($request->current_shift_id)->toBe($currentShift->id)
        ->and($request->requested_shift_id)->toBe($requestedShift->id)
        ->and($request->replacement_user_id)->toBe($replacement->id)
        ->and($request->status)->toBe(ShiftSwapRequest::STATUS_PENDING);
});

test('employee cannot submit duplicate pending shift swap requests for the same schedule', function () {
    [, $employee] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'schedule_id' => $schedule->id,
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Existing request.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleId', $schedule->id)
        ->set('requestedShiftId', $requestedShift->id)
        ->set('reason', 'Trying another request.')
        ->call('store')
        ->assertHasErrors(['scheduleId']);

    expect(ShiftSwapRequest::count())->toBe(1);
});

test('employee can request a shift for an empty schedule date and approval creates the schedule', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $requestedShift = Shift::create(['name' => 'Afternoon', 'start_time' => '15:00', 'end_time' => '23:00']);
    $requestDate = now()->addDays(4)->toDateString();

    $this->actingAs($employee);

    Livewire::test(ShiftSwapRequestPage::class)
        ->call('create')
        ->set('scheduleDate', $requestDate)
        ->set('requestedShiftId', $requestedShift->id)
        ->set('reason', 'Need to add a work schedule for this date.')
        ->call('store')
        ->assertHasNoErrors();

    $request = ShiftSwapRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->schedule_id)->toBeNull()
        ->and($request->schedule_date->toDateString())->toBe($requestDate)
        ->and($request->current_shift_id)->toBeNull()
        ->and(Schedule::query()->where('user_id', $employee->id)->whereDate('date', $requestDate)->exists())->toBeFalse();

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('approveShiftSwap', $request->id);

    $request->refresh();
    $schedule = Schedule::query()
        ->where('user_id', $employee->id)
        ->whereDate('date', $requestDate)
        ->first();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($schedule->shift_id)->toBe($requestedShift->id)
        ->and($schedule->is_off)->toBeFalse();
});

test('manager approval updates the employee schedule and stores approval history', function () {
    [$manager, $employee, $replacement] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Night', 'start_time' => '23:00', 'end_time' => '07:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDays(2)->toDateString(),
    ]);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'schedule_id' => $schedule->id,
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'replacement_user_id' => $replacement->id,
        'reason' => 'Need night coverage this week.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('approveShiftSwap', $request->id);

    $request->refresh();
    $schedule->refresh();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED)
        ->and($request->reviewed_by)->toBe($manager->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($schedule->shift_id)->toBe($requestedShift->id);

    $history = collect(app(TeamApprovalQueryService::class)->history($manager, 'shift-swaps')->items());

    expect($history->pluck('id'))->toContain($request->id);
});

test('manager rejection keeps the original schedule unchanged', function () {
    [$manager, $employee] = createShiftSwapApprovalHierarchy();
    $currentShift = Shift::create(['name' => 'Morning', 'start_time' => '07:00', 'end_time' => '15:00']);
    $requestedShift = Shift::create(['name' => 'Night', 'start_time' => '23:00', 'end_time' => '07:00']);
    $schedule = Schedule::create([
        'user_id' => $employee->id,
        'shift_id' => $currentShift->id,
        'date' => now()->addDays(3)->toDateString(),
    ]);

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'schedule_id' => $schedule->id,
        'current_shift_id' => $currentShift->id,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Need night coverage this week.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'shift-swaps')
        ->call('rejectShiftSwap', $request->id);

    $request->refresh();
    $schedule->refresh();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_REJECTED)
        ->and($request->reviewed_by)->toBe($manager->id)
        ->and($schedule->shift_id)->toBe($currentShift->id);
});

test('admin approval page can approve empty date shift swap requests', function () {
    [, $employee] = createShiftSwapApprovalHierarchy();
    $admin = User::factory()->admin()->create();
    $requestedShift = Shift::create(['name' => 'Evening', 'start_time' => '16:00', 'end_time' => '00:00']);
    $requestDate = now()->addDays(5)->toDateString();

    $request = ShiftSwapRequest::create([
        'user_id' => $employee->id,
        'schedule_date' => $requestDate,
        'requested_shift_id' => $requestedShift->id,
        'reason' => 'Admin approval route coverage.',
        'status' => ShiftSwapRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(ShiftSwapApprovalManager::class)
        ->call('approve', $request->id);

    $request->refresh();
    $schedule = Schedule::query()
        ->where('user_id', $employee->id)
        ->whereDate('date', $requestDate)
        ->first();

    expect($request->status)->toBe(ShiftSwapRequest::STATUS_APPROVED)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->schedule_id)->toBe($schedule->id)
        ->and($schedule->shift_id)->toBe($requestedShift->id);
});

test('admin superadmin and hr can open shift swap approvals page', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $hr = User::factory()->admin()->create();
    $hrRole = Role::query()->where('slug', 'hr')->firstOrFail();

    $hr->roles()->sync([$hrRole->id]);

    $this->actingAs($admin)
        ->get(route('admin.shift-swaps'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('admin.shift-swaps'))
        ->assertOk();

    $this->actingAs($hr)
        ->get(route('admin.shift-swaps'))
        ->assertOk();
});
