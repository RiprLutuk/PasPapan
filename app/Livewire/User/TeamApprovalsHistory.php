<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\Reimbursement;
use App\Models\Overtime;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovalsHistory extends Component
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

    public function render()
    {
        $user = Auth::user();
        $subordinateIds = $user->subordinates->pluck('id');

        $leaves = collect();
        $reimbursements = collect();
        $overtimes = collect();
        $kasbons = collect();

        if ($this->activeTab === 'leaves') {
            $query = Attendance::whereIn('user_id', $subordinateIds)
                ->whereIn('approval_status', ['approved', 'rejected'])
                ->where('status', '!=', 'present');

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $leaves = $query->orderBy('updated_at', 'desc')->paginate(10);
        } elseif ($this->activeTab === 'reimbursements') {
            $query = Reimbursement::whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected']);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $reimbursements = $query->orderBy('updated_at', 'desc')->paginate(10);
        } elseif ($this->activeTab === 'overtimes') {
            $query = Overtime::whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected']);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $overtimes = $query->orderBy('updated_at', 'desc')->paginate(10);
        } else {
            $query = CashAdvance::whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected', 'paid']);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            $kasbons = $query->orderBy('updated_at', 'desc')->paginate(10);
        }

        return view('livewire.user.team-approvals-history', [
            'leaves' => $leaves,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
        ])->layout('layouts.app');
    }
}
