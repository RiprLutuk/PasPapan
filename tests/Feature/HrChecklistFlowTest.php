<?php

use App\Livewire\Admin\HrChecklistManager;
use App\Livewire\User\HrTasksPage;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\Role;
use App\Models\User;
use App\Support\HrChecklistService;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('admin and hr roles can access hr checklists while employees cannot', function () {
    $admin = User::factory()->admin()->create();
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $hrRole = Role::query()->where('slug', 'hr')->firstOrFail();
    $hr->roles()->sync([$hrRole->id]);

    expect(Gate::forUser($admin)->allows('viewHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($hr)->allows('viewHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($hr)->allows('manageHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($employee)->allows('viewHrChecklists'))->toBeFalse();

    $this->actingAs($employee)
        ->get(route('admin.hr-checklists'))
        ->assertForbidden();
});

test('hr can start onboarding checklist case with employee manager and hr tasks', function () {
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $manager = User::factory()->create();
    $employee->update(['manager_id' => $manager->id]);

    $this->actingAs($hr);

    Livewire::test(HrChecklistManager::class)
        ->call('createCase')
        ->set('employeeId', $employee->id)
        ->set('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->set('effectiveDate', '2026-05-10')
        ->call('startCase')
        ->assertHasNoErrors();

    $case = HrChecklistCase::query()
        ->with('tasks')
        ->where('user_id', $employee->id)
        ->where('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->firstOrFail();

    expect($case->status)->toBe(HrChecklistCase::STATUS_ACTIVE)
        ->and($case->tasks)->toHaveCount(4)
        ->and($case->tasks->pluck('assigned_to')->all())->toContain($employee->id, $manager->id, $hr->id);
});

test('assigned employee can complete only their hr checklist task', function () {
    $service = app(HrChecklistService::class);
    $service->ensureDefaultTemplates();

    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $manager = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $employee->update(['manager_id' => $manager->id]);

    $template = HrChecklistTemplate::query()
        ->where('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->with('items')
        ->firstOrFail();

    $case = $service->createCase($employee, $template, $hr, '2026-05-10');
    $employeeTask = $case->tasks()
        ->where('assigned_to', $employee->id)
        ->firstOrFail();
    $managerTask = $case->tasks()
        ->where('assigned_to', $manager->id)
        ->firstOrFail();

    $this->actingAs($employee);

    Livewire::test(HrTasksPage::class)
        ->assertSee(__($employeeTask->title))
        ->assertDontSee(__($managerTask->title))
        ->set("taskNotes.{$employeeTask->id}", 'Submitted from mobile.')
        ->call('updateTask', $employeeTask->id, HrChecklistTask::STATUS_DONE)
        ->assertHasNoErrors();

    expect($employeeTask->refresh()->status)->toBe(HrChecklistTask::STATUS_DONE)
        ->and($employeeTask->completed_by)->toBe($employee->id)
        ->and($employeeTask->notes)->toBe('Submitted from mobile.');

    $this->actingAs($otherEmployee);

    Livewire::test(HrTasksPage::class)
        ->call('updateTask', $managerTask->id, HrChecklistTask::STATUS_DONE)
        ->assertForbidden();
});

test('checklist case is completed when all tasks are closed', function () {
    $service = app(HrChecklistService::class);
    $service->ensureDefaultTemplates();

    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_OFFBOARDING,
        'name' => 'One Task Offboarding',
        'description' => 'Single task template.',
        'is_active' => true,
        'created_by' => $hr->id,
    ]);
    $template->items()->create([
        'title' => 'Confirm final note',
        'category' => 'general',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $case = $service->createCase($employee, $template->fresh('items'), $hr, now());
    $task = $case->tasks()->firstOrFail();

    $service->updateTaskStatus($task, $employee, HrChecklistTask::STATUS_DONE, null);

    expect($case->refresh()->status)->toBe(HrChecklistCase::STATUS_COMPLETED)
        ->and($case->completed_at)->not->toBeNull();
});
