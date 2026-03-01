<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovals extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $activeTab = 'leaves'; // leaves, reimbursements, overtimes, kasbons
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

    protected function isSubordinate($userId)
    {
        return Auth::user()->subordinates->contains('id', $userId);
    }

    public function approveLeave($id)
    {
        $leave = Attendance::find($id);

        if (!$this->isSubordinate($leave->user_id)) {
            return;
        }

        $leave->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

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

    public function approveOvertime($id)
    {
        $overtime = Overtime::find($id);

        if (!$this->isSubordinate($overtime->user_id)) {
            return;
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Overtime request approved.');
    }

    public function rejectOvertime($id)
    {
        $overtime = Overtime::find($id);

        if (!$this->isSubordinate($overtime->user_id)) {
            return;
        }

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', 'Overtime request rejected.');
    }

    public function approveKasbon($id)
    {
        $advance = CashAdvance::find($id);

        if (!$this->isSubordinate($advance->user_id)) {
            return;
        }

        $advance->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
        $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

        $this->dispatch('refresh');
        session()->flash('success', 'Kasbon request approved.');
    }

    public function rejectKasbon($id)
    {
        $advance = CashAdvance::find($id);

        if (!$this->isSubordinate($advance->user_id)) {
            return;
        }

        $advance->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
        $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

        $this->dispatch('refresh');
        session()->flash('success', 'Kasbon request rejected.');
    }

    public function render()
    {
        $user = Auth::user();
        $subordinateIds = $user->subordinates->pluck('id');

        $leaves = collect();
        $reimbursements = collect();
        $overtimes = collect();
        $kasbons = collect();

        if ($this->activeTab === 'leaves') {
            $leaves = Attendance::whereIn('user_id', $subordinateIds)
                ->where('approval_status', 'pending')
                ->where('status', '!=', 'present') // Only leaves
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } elseif ($this->activeTab === 'reimbursements') {
            $reimbursements = Reimbursement::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } elseif ($this->activeTab === 'overtimes') {
            $overtimes = Overtime::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            $kasbons = CashAdvance::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.team-approvals', [
            'leaves' => $leaves,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
        ])->layout('layouts.app');
    }
}
