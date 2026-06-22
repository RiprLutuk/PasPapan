<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ScopesCompanySelection;
use App\Livewire\Concerns\ValidatesCompanyId;
use App\Models\Client;
use App\Models\CompanyBranch;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklistItem;
use App\Models\User;
use App\Support\Contracts\ScopesCompanies;
use App\Support\OperationalWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class OperationalWorkspace extends Component
{
    use InteractsWithBanner;
    use ScopesCompanySelection;
    use ValidatesCompanyId;

    private const BRANCH_TYPES = ['branch', 'store', 'office', 'warehouse', 'site'];

    private const TABS = ['projects', 'tasks', 'clients', 'branches'];

    private const DEFAULT_TAB = 'projects';

    protected OperationalWorkspaceService $operations;

    #[Url(history: true)]
    public string $activeTab = 'projects';

    public string $search = '';

    public string $branchCompanyId = '';

    public string $branchName = '';

    public string $branchType = 'branch';

    public string $branchAddress = '';

    public string $clientCompanyId = '';

    public string $clientName = '';

    public string $clientContactName = '';

    public string $clientContactPhone = '';

    public string $projectCompanyId = '';

    public string $projectClientId = '';

    public string $projectBranchId = '';

    public string $projectManagerId = '';

    public string $projectName = '';

    public string $projectDescription = '';

    public string $taskProjectId = '';

    public string $taskAssignedTo = '';

    public string $taskTitle = '';

    public string $taskPriority = ProjectTask::PRIORITY_NORMAL;

    public string $taskDueDate = '';

    public string $taskChecklist = '';

    public function boot(OperationalWorkspaceService $operations): void
    {
        Gate::authorize('viewOperationsWorkspace');

        $this->operations = $operations;
    }

    public function mount(): void
    {
        $this->normalizeActiveTab();

        $companyId = $this->defaultCompanyId();

        if ($companyId === null) {
            return;
        }

        $this->branchCompanyId = $companyId;
        $this->clientCompanyId = $companyId;
        $this->projectCompanyId = $companyId;
    }

    public function updatedProjectCompanyId(): void
    {
        $this->reset(['projectClientId', 'projectBranchId', 'projectManagerId', 'taskProjectId', 'taskAssignedTo']);
    }

    public function updatedTaskProjectId(): void
    {
        $this->reset('taskAssignedTo');
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
    }

    public function createBranch(): void
    {
        Gate::authorize('manageOperationsWorkspace');

        $validated = $this->validate([
            'branchCompanyId' => $this->companyIdRules(),
            'branchName' => ['required', 'string', 'max:160'],
            'branchType' => ['required', 'string', Rule::in(self::BRANCH_TYPES)],
            'branchAddress' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->operations->createBranch(auth()->user(), [
            'company_id' => (int) $validated['branchCompanyId'],
            'name' => $validated['branchName'],
            'type' => $validated['branchType'],
            'address' => $validated['branchAddress'] ?: null,
            'status' => CompanyBranch::STATUS_ACTIVE,
        ]);

        $this->reset(['branchName', 'branchType', 'branchAddress']);
        $this->branchType = 'branch';
        $this->banner(__('Branch/location created.'));
    }

    public function createClient(): void
    {
        Gate::authorize('manageOperationsWorkspace');

        $validated = $this->validate([
            'clientCompanyId' => $this->companyIdRules(),
            'clientName' => ['required', 'string', 'max:180'],
            'clientContactName' => ['nullable', 'string', 'max:160'],
            'clientContactPhone' => ['nullable', 'string', 'max:60'],
        ]);

        $this->operations->createClient(auth()->user(), [
            'company_id' => (int) $validated['clientCompanyId'],
            'name' => $validated['clientName'],
            'contact_name' => $validated['clientContactName'] ?: null,
            'contact_phone' => $validated['clientContactPhone'] ?: null,
            'status' => Client::STATUS_ACTIVE,
        ]);

        $this->reset(['clientName', 'clientContactName', 'clientContactPhone']);
        $this->banner(__('Client created.'));
    }

    public function createProject(): void
    {
        Gate::authorize('manageOperationsWorkspace');

        $validated = $this->validate([
            'projectCompanyId' => $this->companyIdRules(),
            'projectClientId' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('company_id', (int) $this->projectCompanyId),
            ],
            'projectBranchId' => [
                'nullable',
                'integer',
                Rule::exists('company_branches', 'id')->where('company_id', (int) $this->projectCompanyId),
            ],
            'projectManagerId' => [
                'nullable',
                'string',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->whereNull('company_id')
                    ->orWhere('company_id', (int) $this->projectCompanyId)),
            ],
            'projectName' => ['required', 'string', 'max:180'],
            'projectDescription' => ['nullable', 'string', 'max:1500'],
        ]);

        $project = $this->operations->createProject(auth()->user(), [
            'company_id' => (int) $validated['projectCompanyId'],
            'client_id' => $validated['projectClientId'] !== '' ? $validated['projectClientId'] : null,
            'branch_id' => $validated['projectBranchId'] !== '' ? $validated['projectBranchId'] : null,
            'manager_id' => $validated['projectManagerId'] ?: null,
            'name' => $validated['projectName'],
            'description' => $validated['projectDescription'] ?: null,
            'status' => Project::STATUS_ACTIVE,
        ]);

        $this->taskProjectId = (string) $project->id;
        $this->reset(['projectClientId', 'projectBranchId', 'projectManagerId', 'projectName', 'projectDescription']);
        $this->banner(__('Project created.'));
    }

    public function createTask(): void
    {
        Gate::authorize('manageOperationsWorkspace');

        $validated = $this->validate([
            'taskProjectId' => ['required', 'integer', Rule::exists('projects', 'id')],
            'taskAssignedTo' => ['nullable', 'string', Rule::exists('users', 'id')],
            'taskTitle' => ['required', 'string', 'max:180'],
            'taskPriority' => ['required', Rule::in([ProjectTask::PRIORITY_LOW, ProjectTask::PRIORITY_NORMAL, ProjectTask::PRIORITY_HIGH])],
            'taskDueDate' => ['nullable', 'date'],
            'taskChecklist' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = Project::query()->findOrFail((int) $validated['taskProjectId']);

        if (! $this->assigneeBelongsToProjectCompany($validated['taskAssignedTo'] ?? null, $project)) {
            throw ValidationException::withMessages([
                'taskAssignedTo' => __('Selected user does not belong to the selected project company.'),
            ]);
        }

        $checklistItems = collect(preg_split('/\r\n|\r|\n/', (string) $validated['taskChecklist']))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();

        $this->operations->createTask(auth()->user(), $project, [
            'assigned_to' => $validated['taskAssignedTo'] ?: null,
            'title' => $validated['taskTitle'],
            'priority' => $validated['taskPriority'],
            'due_date' => $validated['taskDueDate'] ?: null,
            'status' => ProjectTask::STATUS_TODO,
        ], $checklistItems);

        $this->reset(['taskAssignedTo', 'taskTitle', 'taskPriority', 'taskDueDate', 'taskChecklist']);
        $this->taskPriority = ProjectTask::PRIORITY_NORMAL;
        $this->banner(__('Task created.'));
    }

    public function updateTaskStatus(int $taskId, string $status): void
    {
        Gate::authorize('manageOperationsWorkspace');

        validator(
            ['status' => $status],
            ['status' => ['required', Rule::in([ProjectTask::STATUS_TODO, ProjectTask::STATUS_IN_PROGRESS, ProjectTask::STATUS_DONE])]],
        )->validate();

        $task = ProjectTask::query()->findOrFail($taskId);
        $this->operations->updateTask(auth()->user(), $task, ['status' => $status]);
    }

    public function toggleChecklistItem(int $itemId): void
    {
        Gate::authorize('manageOperationsWorkspace');

        $item = ProjectTaskChecklistItem::query()->with('task')->findOrFail($itemId);
        $this->operations->toggleChecklistItem(auth()->user(), $item);
    }

    public function render()
    {
        $user = auth()->user();
        $companyIds = $this->scopedCompanyIds($user);
        $companies = $this->companyOptions($companyIds);

        $branches = CompanyBranch::query()
            ->with('company:id,name')
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        $clients = Client::query()
            ->with('company:id,name')
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        $projects = Project::query()
            ->with([
                'company:id,name',
                'client:id,name',
                'branch:id,name',
                'manager:id,name',
                'tasks.checklistItems',
                'tasks.assignee:id,name',
                'tasks.visitEvidences.user:id,name',
            ])
            ->withCount('tasks')
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();
        $projectFinancials = $this->operations->projectFinancialSummaries($projects->pluck('id')->all());

        $users = User::query()
            ->where('group', '!=', 'superadmin')
            ->where(fn (Builder $query) => $query->whereNull('company_id')->orWhereIn('company_id', $companyIds))
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'email']);

        $selectedProjectCompanyId = $this->scopedCompanyId($companyIds, $this->projectCompanyId);
        $selectedTaskProject = $projects->firstWhere('id', (int) $this->taskProjectId);
        $selectedTaskCompanyId = $selectedTaskProject?->company_id;

        return view('livewire.admin.operational-workspace', [
            'companies' => $companies,
            'branches' => $branches,
            'clients' => $clients,
            'projects' => $projects,
            'projectClientOptions' => $selectedProjectCompanyId === null
                ? $clients
                : $clients->where('company_id', $selectedProjectCompanyId)->values(),
            'projectBranchOptions' => $selectedProjectCompanyId === null
                ? $branches
                : $branches->where('company_id', $selectedProjectCompanyId)->values(),
            'projectManagerOptions' => $selectedProjectCompanyId === null
                ? $users
                : $users->filter(fn (User $option): bool => $option->company_id === null || (int) $option->company_id === $selectedProjectCompanyId)->values(),
            'taskAssigneeOptions' => $selectedTaskCompanyId === null
                ? $users
                : $users->filter(fn (User $option): bool => $option->company_id === null || (int) $option->company_id === (int) $selectedTaskCompanyId)->values(),
            'projectFinancials' => $projectFinancials,
            'users' => $users,
            'branchTypes' => self::BRANCH_TYPES,
            'canManage' => $user->can('manageOperationsWorkspace'),
        ]);
    }

    protected function companyScopeService(): ScopesCompanies
    {
        return $this->operations;
    }

    private function assigneeBelongsToProjectCompany(mixed $userId, Project $project): bool
    {
        if ($userId === null || $userId === '') {
            return true;
        }

        return User::query()
            ->whereKey($userId)
            ->where(fn (Builder $query) => $query
                ->whereNull('company_id')
                ->orWhere('company_id', $project->company_id))
            ->exists();
    }
}
