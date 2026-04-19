<?php

namespace App\Livewire\Admin;

use App\Models\Reimbursement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ReimbursementManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $statusFilter = 'pending';
    public $search = '';

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

        $user = auth()->user();
        $isFinanceHead = ($user->is_admin || $user->is_superadmin || ($user->jobTitle?->jobLevel?->rank <= 2 && $user->division && strtolower($user->division->name) === 'finance'));

        if ($isFinanceHead) {
            $reimbursement->update([
                'status' => 'approved',
                'finance_approved_by' => $user->id,
                'finance_approved_at' => now(),
                'approved_by' => $user->id
            ]);
        } else {
            $reimbursement->update([
                'status' => 'pending_finance',
                'head_approved_by' => $user->id,
                'head_approved_at' => now(),
            ]);
        }

        $reimbursement->user->notify(new \App\Notifications\ReimbursementStatusUpdated($reimbursement));
        $this->dispatch('saved');
    }

    public function reject($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $this->authorize('reject', $reimbursement);

        $user = auth()->user();
        $isFinanceHead = ($user->is_admin || $user->is_superadmin || ($user->jobTitle?->jobLevel?->rank <= 2 && $user->division && strtolower($user->division->name) === 'finance'));

        $reimbursement->update(['status' => 'rejected']);

        if ($isFinanceHead) {
            $reimbursement->update([
                'finance_approved_by' => $user->id,
                'finance_approved_at' => now(),
                'approved_by' => $user->id // fallback
            ]);
        } else {
            $reimbursement->update([
                'head_approved_by' => $user->id,
                'head_approved_at' => now(),
            ]);
        }

        $reimbursement->user->notify(new \App\Notifications\ReimbursementStatusUpdated($reimbursement));
        $this->dispatch('saved');
    }

    public function render()
    {
        $this->authorize('viewAny', Reimbursement::class);

        $user = auth()->user();
        $myRank = $user->jobTitle?->jobLevel?->rank;
        $isFinanceHead = ($myRank && $myRank <= 2 && $user->division && strtolower($user->division->name) === 'finance');

        $reimbursements = Reimbursement::query()
            ->with(['user', 'approvedBy', 'headApprover', 'financeApprover'])
            ->when(!$user->is_admin && !$user->is_superadmin, function ($q) use ($user, $isFinanceHead) {
                if ($isFinanceHead) {
                    return $q->where(function ($qq) use ($user) {
                        $qq->where('status', 'pending_finance')
                            ->orWhereIn('user_id', $user->subordinates->pluck('id'));
                    });
                } else {
                    return $q->whereIn('user_id', $user->subordinates->pluck('id'));
                }
            })
            ->when($this->statusFilter, function ($query) {
                return $query->where('status', $this->statusFilter);
            })
            ->when($this->search, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.reimbursement-manager', [
            'reimbursements' => $reimbursements,
        ])->layout('layouts.app');
    }
}
