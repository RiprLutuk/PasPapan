<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Support\ApprovalActorService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovalsHistory extends Component
{
    use WithPagination;

    protected TeamApprovalQueryService $teamApprovalQueries;
    protected ApprovalActorService $approvalActors;

    #[Url(history: true)]
    public $activeTab = 'leaves'; // leaves, attendance-corrections, reimbursements, overtimes, kasbons
    public $search = '';

    public function boot(TeamApprovalQueryService $teamApprovalQueries, ApprovalActorService $approvalActors): void
    {
        $this->teamApprovalQueries = $teamApprovalQueries;
        $this->approvalActors = $approvalActors;
    }

    public function mount()
    {
        $user = Auth::user();
        if (! $this->approvalActors->hasSubordinates($user)) {
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
        $leaves = collect();
        $attendanceCorrections = collect();
        $reimbursements = collect();
        $overtimes = collect();
        $kasbons = collect();
        $result = $this->teamApprovalQueries->history(Auth::user(), (string) $this->activeTab, (string) $this->search);

        match ($this->activeTab) {
            'attendance-corrections' => $attendanceCorrections = $result,
            'reimbursements' => $reimbursements = $result,
            'overtimes' => $overtimes = $result,
            'kasbons' => $kasbons = $result,
            default => $leaves = $result,
        };

        return view('livewire.user.team-approvals-history', [
            'leaves' => $leaves,
            'attendanceCorrections' => $attendanceCorrections,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
        ])->layout('layouts.app');
    }
}
