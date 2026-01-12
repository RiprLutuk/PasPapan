<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovals extends Component
{
    use WithPagination;

    public $activeTab = 'leaves'; // leaves, reimbursements
    public $search = '';

    public function mount()
    {
        $user = Auth::user();
        if ($user->subordinates->isEmpty()) {
            return redirect()->route('home');
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function approveLeave($id)
    {
        $leave = Attendance::find($id);
        
        // Security: Ensure this leave belongs to a subordinate
        if (!$this->isSubordinate($leave->user_id)) {
            return;
        }

        $leave->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
        ]);
        
        // Notify user (Optional: Implement notification logic)
        
        $this->dispatch('refresh');
        session()->flash('success', 'Leave request approved.');
    }

    public function rejectLeave($id)
    {
        $leave = Attendance::find($id);

        if (!$this->isSubordinate($leave->user_id)) {
            return;
        }

        $leave->update([
            'approval_status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Leave request rejected.');
    }

    public function approveReimbursement($id)
    {
        $reimbursement = Reimbursement::find($id);

        if (!$this->isSubordinate($reimbursement->user_id)) {
            return;
        }

        $reimbursement->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Reimbursement request approved.');
    }

    public function rejectReimbursement($id)
    {
        $reimbursement = Reimbursement::find($id);

        if (!$this->isSubordinate($reimbursement->user_id)) {
            return;
        }

        $reimbursement->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Reimbursement request rejected.');
    }

    protected function isSubordinate($userId)
    {
        return Auth::user()->subordinates->contains('id', $userId);
    }

    public function render()
    {
        $user = Auth::user();
        $subordinateIds = $user->subordinates->pluck('id');

        $leaves = collect();
        $reimbursements = collect();

        if ($this->activeTab === 'leaves') {
            $leaves = Attendance::whereIn('user_id', $subordinateIds)
                ->where('approval_status', 'pending')
                ->where('status', '!=', 'present') // Only leaves
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            $reimbursements = Reimbursement::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.team-approvals', [
            'leaves' => $leaves,
            'reimbursements' => $reimbursements,
        ])->layout('layouts.app');
    }
}
