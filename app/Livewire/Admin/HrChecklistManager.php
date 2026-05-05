<?php

namespace App\Livewire\Admin;

use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\User;
use App\Support\HrChecklistService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HrChecklistManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $activeTab = 'cases';

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'active';

    public bool $showCreateCaseModal = false;

    public ?int $selectedCaseId = null;

    public ?string $employeeId = null;

    public string $type = HrChecklistTemplate::TYPE_ONBOARDING;

    public ?int $templateId = null;

    public string $effectiveDate = '';

    /** @var array<int, string> */
    public array $taskNotes = [];

    protected HrChecklistService $checklists;

    public function boot(HrChecklistService $checklists): void
    {
        $this->checklists = $checklists;
    }

    public function mount(): void
    {
        Gate::authorize('viewHrChecklists');
        $this->checklists->ensureDefaultTemplates();
        $this->effectiveDate = now()->toDateString();
        $this->setDefaultTemplate();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['cases', 'templates'], true) ? $tab : 'cases';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->setDefaultTemplate();
    }

    public function createCase(): void
    {
        Gate::authorize('manageHrChecklists');
        $this->resetValidation();
        $this->employeeId = null;
        $this->effectiveDate = now()->toDateString();
        $this->type = HrChecklistTemplate::TYPE_ONBOARDING;
        $this->setDefaultTemplate();
        $this->showCreateCaseModal = true;
    }

    public function startCase(): void
    {
        Gate::authorize('manageHrChecklists');

        $validated = $this->validate([
            'employeeId' => ['required', 'exists:users,id'],
            'type' => ['required', Rule::in(array_keys(HrChecklistTemplate::types()))],
            'templateId' => ['required', 'exists:hr_checklist_templates,id'],
            'effectiveDate' => ['required', 'date'],
        ]);

        $employee = User::query()
            ->where('group', 'user')
            ->managedBy(auth()->user())
            ->findOrFail($validated['employeeId']);

        $template = HrChecklistTemplate::query()
            ->where('is_active', true)
            ->where('type', $validated['type'])
            ->with('items')
            ->findOrFail($validated['templateId']);

        $case = $this->checklists->createCase($employee, $template, auth()->user(), $validated['effectiveDate']);

        $this->selectedCaseId = $case->id;
        $this->showCreateCaseModal = false;
        session()->flash('success', __('HR checklist case started.'));
    }

    public function selectCase(int $caseId): void
    {
        $case = HrChecklistCase::with(['user', 'tasks.assignee'])->findOrFail($caseId);
        $this->authorize('view', $case);

        $this->selectedCaseId = $case->id;
        $this->taskNotes = $case->tasks
            ->mapWithKeys(fn (HrChecklistTask $task): array => [$task->id => (string) ($task->notes ?? '')])
            ->all();
    }

    public function updateTask(int $taskId, string $status): void
    {
        $task = HrChecklistTask::with('case.user')->findOrFail($taskId);
        $this->authorize('update', $task);

        if (! array_key_exists($status, HrChecklistTask::statuses())) {
            return;
        }

        $this->checklists->updateTaskStatus($task, auth()->user(), $status, $this->taskNotes[$taskId] ?? null);
        session()->flash('success', __('Task updated.'));
    }

    public function cancelCase(int $caseId): void
    {
        $case = HrChecklistCase::findOrFail($caseId);
        $this->authorize('cancel', $case);

        $this->checklists->cancelCase($case);
        session()->flash('success', __('Checklist case cancelled.'));
    }

    protected function setDefaultTemplate(): void
    {
        $this->templateId = HrChecklistTemplate::query()
            ->where('type', $this->type)
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');
    }

    public function render()
    {
        $casesQuery = HrChecklistCase::query()
            ->with(['user.division', 'user.jobTitle', 'template'])
            ->withCount([
                'tasks',
                'tasks as closed_tasks_count' => fn (Builder $query) => $query->whereIn('status', HrChecklistTask::closedStatuses()),
            ])
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery
                        ->whereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('nip', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('template', fn (Builder $templateQuery) => $templateQuery->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest('effective_date')
            ->latest();

        $selectedCase = $this->selectedCaseId
            ? HrChecklistCase::with(['user.directManager', 'template', 'tasks.assignee', 'tasks.completer'])
                ->withCount([
                    'tasks',
                    'tasks as closed_tasks_count' => fn (Builder $query) => $query->whereIn('status', HrChecklistTask::closedStatuses()),
                ])
                ->find($this->selectedCaseId)
            : null;

        return view('livewire.admin.hr-checklist-manager', [
            'cases' => $casesQuery->paginate(10),
            'selectedCase' => $selectedCase,
            'templates' => HrChecklistTemplate::with('items')->orderBy('type')->orderBy('name')->get(),
            'templateOptions' => HrChecklistTemplate::query()
                ->where('type', $this->type)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'employeeOptions' => User::query()
                ->where('group', 'user')
                ->managedBy(auth()->user())
                ->orderBy('name')
                ->get(['id', 'name', 'nip', 'division_id', 'job_title_id']),
            'types' => HrChecklistTemplate::types(),
            'caseStatuses' => HrChecklistCase::statuses(),
            'taskStatuses' => HrChecklistTask::statuses(),
        ]);
    }
}
