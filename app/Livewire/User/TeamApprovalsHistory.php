<?php

namespace App\Livewire\User;

use App\Support\ApprovalActorService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovalsHistory extends Component
{
    use WithPagination;

    private const TABS = [
        'leaves',
        'attendance-corrections',
        'shift-swaps',
        'reimbursements',
        'overtimes',
        'kasbons',
    ];

    protected TeamApprovalQueryService $teamApprovalQueries;

    protected ApprovalActorService $approvalActors;

    #[Url(history: true)]
    public $activeTab = 'leaves'; // leaves, attendance-corrections, shift-swaps, reimbursements, overtimes, kasbons

    public $search = '';

    public function boot(TeamApprovalQueryService $teamApprovalQueries, ApprovalActorService $approvalActors): void
    {
        $this->teamApprovalQueries = $teamApprovalQueries;
        $this->approvalActors = $approvalActors;
    }

    public function mount()
    {
        Gate::authorize('reviewSubordinateRequests');
        $this->normalizeActiveTab();
    }

    public function switchTab($tab)
    {
        if (! in_array($tab, self::TABS, true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $leaves = collect();
        $attendanceCorrections = collect();
        $shiftSwapRequests = collect();
        $reimbursements = collect();
        $overtimes = collect();
        $kasbons = collect();
        $result = $this->teamApprovalQueries->history(Auth::user(), (string) $this->activeTab, (string) $this->search);

        match ($this->activeTab) {
            'attendance-corrections' => $attendanceCorrections = $result,
            'shift-swaps' => $shiftSwapRequests = $result,
            'reimbursements' => $reimbursements = $result,
            'overtimes' => $overtimes = $result,
            'kasbons' => $kasbons = $result,
            default => $leaves = $result,
        };

        return view('livewire.user.team-approvals-history', [
            'leaves' => $leaves,
            'attendanceCorrections' => $attendanceCorrections,
            'shiftSwapRequests' => $shiftSwapRequests,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
        ])->layout('layouts.app');
    }

    private function normalizeActiveTab(): void
    {
        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'leaves';
        }
    }
}
