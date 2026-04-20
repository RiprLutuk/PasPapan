<?php

namespace App\Livewire\Admin;

use App\Models\Reimbursement;
use App\Support\ReimbursementApprovalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ReimbursementManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected ReimbursementApprovalService $reimbursementApprovals;

    public $statusFilter = 'pending';
    public $search = '';

    public function boot(ReimbursementApprovalService $reimbursementApprovals): void
    {
        $this->reimbursementApprovals = $reimbursementApprovals;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function approve($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $this->authorize('approve', $reimbursement);
        $message = $this->reimbursementApprovals->approve($reimbursement, auth()->user());
        session()->flash('success', $message);
        $this->dispatch('saved');
    }

    public function reject($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $this->authorize('reject', $reimbursement);
        $message = $this->reimbursementApprovals->reject($reimbursement, auth()->user());
        session()->flash('success', $message);
        $this->dispatch('saved');
    }

    public function render()
    {
        $this->authorize('viewAny', Reimbursement::class);
        $reimbursements = $this->reimbursementApprovals
            ->managementQuery(auth()->user(), (string) $this->statusFilter, (string) $this->search)
            ->paginate(10);

        return view('livewire.admin.reimbursement-manager', [
            'reimbursements' => $reimbursements,
        ])->layout('layouts.app');
    }
}
