<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Support\LeaveApprovalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveApproval extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected LeaveApprovalService $leaveApprovals;

    public string $search = '';
    public $rejectionNote;
    public $selectedIds = [];
    public $confirmingRejection = false;
    public $statusFilter = 'all';
    public string $requestTypeFilter = 'all';

    public function boot(LeaveApprovalService $leaveApprovals): void
    {
        $this->leaveApprovals = $leaveApprovals;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRequestTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('manageLeaveApprovals');

        $groupedLeaves = $this->leaveApprovals->groupedRequests(
            Auth::user(),
            (string) $this->statusFilter,
            (string) $this->requestTypeFilter,
            (string) $this->search,
        );

        return view('livewire.admin.leave-approval', [
            'groupedLeaves' => $groupedLeaves,
        ]);
    }

    public function approve($ids)
    {
        $this->authorize('manageLeaveApprovals');

        if (! is_array($ids)) {
            $ids = [$ids];
        }

        $this->leaveApprovals->approve($ids, Auth::user());

        $this->dispatch('saved');
    }

    public function confirmReject($ids)
    {
        $this->authorize('manageLeaveApprovals');

        if (! is_array($ids)) {
            $ids = [$ids];
        }
        $this->selectedIds = $ids;
        $this->confirmingRejection = true;
    }

    public function reject()
    {
        $this->authorize('manageLeaveApprovals');

        $this->leaveApprovals->reject($this->selectedIds, Auth::user(), $this->rejectionNote);

        $this->confirmingRejection = false;
        $this->rejectionNote = '';
        $this->selectedIds = [];
        $this->dispatch('saved');
    }
}
