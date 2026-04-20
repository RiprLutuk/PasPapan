<?php

use App\Livewire\Admin\MasterData\DivisionComponent;
use App\Livewire\Admin\MasterData\EducationComponent;
use App\Livewire\Admin\MasterData\JobTitleComponent;
use App\Livewire\Admin\MasterData\ShiftComponent;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Education;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

test('superadmin can delete division and linked records are detached', function () {
    $superadmin = User::factory()->admin(true)->create();
    $division = Division::create(['name' => 'Operations']);
    $jobLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $jobTitle = JobTitle::create([
        'name' => 'Ops Manager',
        'job_level_id' => $jobLevel->id,
        'division_id' => $division->id,
    ]);
    $user = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $jobTitle->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(DivisionComponent::class)
        ->call('confirmDeletion', $division->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Division::query()->find($division->id))->toBeNull();
    expect($user->fresh()->division_id)->toBeNull();
    expect($jobTitle->fresh()->division_id)->toBeNull();
});

test('superadmin can delete education and linked users are detached', function () {
    $superadmin = User::factory()->admin(true)->create();
    $education = Education::create(['name' => 'S1 Teknik']);
    $user = User::factory()->create(['education_id' => $education->id]);

    $this->actingAs($superadmin);

    Livewire::test(EducationComponent::class)
        ->call('confirmDeletion', $education->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Education::query()->find($education->id))->toBeNull();
    expect($user->fresh()->education_id)->toBeNull();
});

test('superadmin can delete job title and linked users are detached', function () {
    $superadmin = User::factory()->admin(true)->create();
    $jobLevel = JobLevel::create(['name' => 'Supervisor', 'rank' => 3]);
    $jobTitle = JobTitle::create([
        'name' => 'Field Supervisor',
        'job_level_id' => $jobLevel->id,
    ]);
    $user = User::factory()->create(['job_title_id' => $jobTitle->id]);

    $this->actingAs($superadmin);

    Livewire::test(JobTitleComponent::class)
        ->call('confirmDeletion', $jobTitle->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(JobTitle::query()->find($jobTitle->id))->toBeNull();
    expect($user->fresh()->job_title_id)->toBeNull();
});

test('superadmin can delete shift and linked attendance is detached while schedules are removed', function () {
    $superadmin = User::factory()->admin(true)->create();
    $user = User::factory()->create();
    $shift = Shift::create([
        'name' => 'Morning',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $attendance = Attendance::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'status' => 'present',
    ]);

    $schedule = Schedule::create([
        'user_id' => $user->id,
        'shift_id' => $shift->id,
        'date' => now()->addDay()->toDateString(),
        'is_off' => false,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ShiftComponent::class)
        ->call('confirmDeletion', $shift->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Shift::query()->find($shift->id))->toBeNull();
    expect($attendance->fresh()->shift_id)->toBeNull();
    expect(Schedule::query()->find($schedule->id))->toBeNull();
});
