<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovalsHistory extends Component
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

    // Removed action methods as this is history view

    public function render()
    {
        $user = Auth::user();
        $subordinateIds = $user->subordinates->pluck('id');

        $leaves = collect();
        $reimbursements = collect();

        if ($this->activeTab === 'leaves') {
            $query = Attendance::whereIn('user_id', $subordinateIds)
                ->whereIn('approval_status', ['approved', 'rejected'])
                ->where('status', '!=', 'present');

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $leaves = $query->orderBy('updated_at', 'desc')
                ->paginate(10);
        } else {
            $query = Reimbursement::whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected']);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $reimbursements = $query->orderBy('updated_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.team-approvals-history', [
            'leaves' => $leaves,
            'reimbursements' => $reimbursements,
        ])->layout('layouts.app');
    }
}
