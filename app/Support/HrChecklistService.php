<?php

namespace App\Support;

use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HrChecklistService
{
    public function ensureDefaultTemplates(): void
    {
        foreach ($this->defaultTemplates() as $type => $templateData) {
            $template = HrChecklistTemplate::query()->firstOrCreate(
                ['type' => $type, 'name' => $templateData['name']],
                [
                    'description' => $templateData['description'],
                    'is_active' => true,
                ]
            );

            if ($template->items()->exists()) {
                continue;
            }

            foreach ($templateData['items'] as $index => $item) {
                $template->items()->create([
                    ...$item,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    public function createCase(User $employee, HrChecklistTemplate $template, User $actor, Carbon|string $effectiveDate): HrChecklistCase
    {
        $effectiveDate = Carbon::parse($effectiveDate)->startOfDay();
        $template->loadMissing('items');

        return DB::transaction(function () use ($employee, $template, $actor, $effectiveDate): HrChecklistCase {
            $case = HrChecklistCase::create([
                'template_id' => $template->id,
                'user_id' => $employee->id,
                'type' => $template->type,
                'status' => HrChecklistCase::STATUS_ACTIVE,
                'effective_date' => $effectiveDate->toDateString(),
                'started_by' => $actor->id,
            ]);

            foreach ($template->items as $item) {
                $case->tasks()->create([
                    'template_item_id' => $item->id,
                    'assigned_to' => $this->resolveAssigneeId($item, $employee, $actor),
                    'title' => $item->title,
                    'description' => $item->description,
                    'category' => $item->category,
                    'due_date' => $effectiveDate->copy()->addDays((int) $item->due_offset_days)->toDateString(),
                    'status' => HrChecklistTask::STATUS_PENDING,
                ]);
            }

            return $case->load(['user', 'template', 'tasks.assignee']);
        });
    }

    public function updateTaskStatus(HrChecklistTask $task, User $actor, string $status, ?string $notes = null): HrChecklistTask
    {
        $closed = in_array($status, HrChecklistTask::closedStatuses(), true);

        DB::transaction(function () use ($task, $actor, $status, $notes, $closed): void {
            $task->update([
                'status' => $status,
                'notes' => $notes,
                'completed_by' => $closed ? $actor->id : null,
                'completed_at' => $closed ? now() : null,
            ]);

            $this->refreshCaseStatus($task->case()->firstOrFail());
        });

        return $task->refresh();
    }

    public function refreshCaseStatus(HrChecklistCase $case): void
    {
        if ($case->status === HrChecklistCase::STATUS_CANCELLED) {
            return;
        }

        $hasOpenTasks = $case->tasks()
            ->whereNotIn('status', HrChecklistTask::closedStatuses())
            ->exists();

        $case->update([
            'status' => $hasOpenTasks ? HrChecklistCase::STATUS_ACTIVE : HrChecklistCase::STATUS_COMPLETED,
            'completed_at' => $hasOpenTasks ? null : now(),
        ]);
    }

    public function cancelCase(HrChecklistCase $case): void
    {
        $case->update([
            'status' => HrChecklistCase::STATUS_CANCELLED,
            'completed_at' => null,
        ]);
    }

    protected function resolveAssigneeId(HrChecklistTemplateItem $item, User $employee, User $actor): ?string
    {
        return match ($item->default_assignee_type) {
            HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE => $employee->id,
            HrChecklistTemplateItem::ASSIGNEE_MANAGER => $employee->manager_id ?: $actor->id,
            default => $actor->id,
        };
    }

    protected function defaultTemplates(): array
    {
        return [
            HrChecklistTemplate::TYPE_ONBOARDING => [
                'name' => 'Default Onboarding Checklist',
                'description' => 'Prepare access, policy reading, first-day setup, and employee data.',
                'items' => [
                    [
                        'title' => 'Verify employee profile data',
                        'description' => 'Confirm NIP, contact, division, job title, manager, and employment status.',
                        'category' => 'employee_data',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
                        'due_offset_days' => -2,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Collect required employee documents',
                        'description' => 'Use Document Requests when uploads or generated letters are needed.',
                        'category' => 'documents',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE,
                        'due_offset_days' => -1,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Prepare schedule, attendance location, and first shift',
                        'description' => 'Confirm the employee can see the right schedule and attendance flow.',
                        'category' => 'attendance',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
                        'due_offset_days' => 0,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Review first-week expectations with manager',
                        'description' => 'Manager confirms role priorities, attendance expectations, and communication channel.',
                        'category' => 'manager',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_MANAGER,
                        'due_offset_days' => 3,
                        'is_required' => true,
                    ],
                ],
            ],
            HrChecklistTemplate::TYPE_OFFBOARDING => [
                'name' => 'Default Offboarding Checklist',
                'description' => 'Collect company property, close access, and prepare final HR notes.',
                'items' => [
                    [
                        'title' => 'Confirm final working date and employee status',
                        'description' => 'Update employment status after approval and record the effective date.',
                        'category' => 'employee_data',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
                        'due_offset_days' => -3,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Collect company assets',
                        'description' => 'Use My Assets or the admin asset page to complete return evidence.',
                        'category' => 'assets',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_MANAGER,
                        'due_offset_days' => -1,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Prepare final payroll and document follow-up',
                        'description' => 'Check pending reimbursements, kasbon, payslip, and employment letters if needed.',
                        'category' => 'finance',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
                        'due_offset_days' => 0,
                        'is_required' => true,
                    ],
                    [
                        'title' => 'Confirm handover notes',
                        'description' => 'Manager confirms work handover and pending operational notes.',
                        'category' => 'manager',
                        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_MANAGER,
                        'due_offset_days' => 1,
                        'is_required' => true,
                    ],
                ],
            ],
        ];
    }
}
