<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Overtime;
use App\Support\OvertimeApprovalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class OvertimeManager extends Component
{
    use WithPagination;

    protected OvertimeApprovalService $overtimeApprovals;

    public string $search = '';
    public $rejectionReason;
    public $selectedId = null;
    public $confirmingRejection = false;
    public $statusFilter = 'pending';

    public function boot(OvertimeApprovalService $overtimeApprovals): void
    {
        Gate::authorize('manageOvertime');
        $this->overtimeApprovals = $overtimeApprovals;
    }

    public function render()
    {
        $overtimes = $this->overtimeApprovals
            ->managementQuery((string) $this->statusFilter, (string) $this->search)
            ->paginate(15);

        return view('livewire.admin.overtime-manager', [
            'overtimes' => $overtimes
        ]);
    }

    public function approve($id)
    {
        $overtime = Overtime::findOrFail($id);
        $this->overtimeApprovals->approve($overtime, Auth::user());

        $this->dispatch('toast', type: 'success', message: __('Overtime approved.'));
    }

    public function confirmReject($id)
    {
        $this->selectedId = $id;
        $this->confirmingRejection = true;
    }

    public function reject()
    {
        if (!$this->selectedId) return;

        $overtime = Overtime::findOrFail($this->selectedId);
        $this->overtimeApprovals->reject($overtime, Auth::user(), $this->rejectionReason);

        $this->confirmingRejection = false;
        $this->rejectionReason = '';
        $this->selectedId = null;

        $this->dispatch('toast', type: 'success', message: __('Overtime rejected.'));
    }

    public function cancelReject()
    {
        $this->confirmingRejection = false;
        $this->rejectionReason = '';
        $this->selectedId = null;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
