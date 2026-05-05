<?php

namespace App\Livewire\User;

use App\Models\HrChecklistTask;
use App\Support\HrChecklistService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class HrTasksPage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(history: true)]
    public string $statusFilter = 'pending';

    public string $search = '';

    /** @var array<int, string> */
    public array $taskNotes = [];

    protected HrChecklistService $checklists;

    public function boot(HrChecklistService $checklists): void
    {
        $this->checklists = $checklists;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateTask(int $taskId, string $status): void
    {
        $task = HrChecklistTask::with('case.user')->findOrFail($taskId);
        $this->authorize('update', $task);

        validator(
            ['status' => $status],
            ['status' => ['required', Rule::in(array_keys(HrChecklistTask::statuses()))]]
        )->validate();

        $this->checklists->updateTaskStatus($task, auth()->user(), $status, $this->taskNotes[$taskId] ?? null);
        session()->flash('success', __('Task updated.'));
    }

    public function render()
    {
        $tasks = HrChecklistTask::query()
            ->with(['case.user', 'case.template', 'assignee'])
            ->where('assigned_to', auth()->id())
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('case.user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest()
            ->paginate(10);

        return view('livewire.user.hr-tasks-page', [
            'tasks' => $tasks,
            'statuses' => HrChecklistTask::statuses(),
        ])->layout('layouts.app');
    }
}
