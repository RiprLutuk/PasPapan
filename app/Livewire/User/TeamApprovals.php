<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Support\ApprovalActorService;
use App\Support\AttendanceCorrectionService;
use App\Support\CashAdvanceApprovalService;
use App\Support\OvertimeApprovalService;
use App\Support\ReimbursementApprovalService;
use App\Support\ShiftSwapRequestService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamApprovals extends Component
{
    use WithPagination;

    protected TeamApprovalQueryService $teamApprovalQueries;

    protected ApprovalActorService $approvalActors;

    protected AttendanceCorrectionService $attendanceCorrectionApprovals;

    protected ReimbursementApprovalService $reimbursementApprovals;

    protected OvertimeApprovalService $overtimeApprovals;

    protected CashAdvanceApprovalService $cashAdvanceApprovals;

    protected ShiftSwapRequestService $shiftSwapApprovals;

    #[Url(history: true)]
    public $activeTab = 'leaves'; // leaves, attendance-corrections, shift-swaps, reimbursements, overtimes, kasbons

    public $search = '';

    public function boot(
        TeamApprovalQueryService $teamApprovalQueries,
        ApprovalActorService $approvalActors,
        AttendanceCorrectionService $attendanceCorrectionApprovals,
        ReimbursementApprovalService $reimbursementApprovals,
        OvertimeApprovalService $overtimeApprovals,
        CashAdvanceApprovalService $cashAdvanceApprovals,
        ShiftSwapRequestService $shiftSwapApprovals,
    ): void {
        $this->teamApprovalQueries = $teamApprovalQueries;
        $this->approvalActors = $approvalActors;
        $this->attendanceCorrectionApprovals = $attendanceCorrectionApprovals;
        $this->reimbursementApprovals = $reimbursementApprovals;
        $this->overtimeApprovals = $overtimeApprovals;
        $this->cashAdvanceApprovals = $cashAdvanceApprovals;
        $this->shiftSwapApprovals = $shiftSwapApprovals;
    }

    public function mount()
    {
        Gate::authorize('reviewSubordinateRequests');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    protected function isSubordinate($userId)
    {
        return $this->approvalActors->subordinateIds(Auth::user())->contains($userId);
    }

    public function approveLeave($id)
    {
        $leave = Attendance::find($id);

        if (! $leave || ! $this->isSubordinate($leave->user_id)) {
            return;
        }

        $leave->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', __('Leave request approved.'));
    }

    public function rejectLeave($id)
    {
        $leave = Attendance::find($id);

        if (! $leave || ! $this->isSubordinate($leave->user_id)) {
            return;
        }

        $leave->update([
            'approval_status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $this->dispatch('refresh');
        session()->flash('success', __('Leave request rejected.'));
    }

    public function approveReimbursement($id)
    {
        $reimbursement = Reimbursement::find($id);

        if (! $reimbursement || ! $this->isSubordinate($reimbursement->user_id)) {
            return;
        }

        session()->flash('success', $this->reimbursementApprovals->approve($reimbursement, Auth::user()));
        $this->dispatch('refresh');
    }

    public function rejectReimbursement($id)
    {
        $reimbursement = Reimbursement::find($id);

        if (! $reimbursement || ! $this->isSubordinate($reimbursement->user_id)) {
            return;
        }

        session()->flash('success', $this->reimbursementApprovals->reject($reimbursement, Auth::user()));
        $this->dispatch('refresh');
    }

    public function approveAttendanceCorrection($id)
    {
        $correction = AttendanceCorrection::find($id);

        if (! $correction || ! $this->isSubordinate($correction->user_id)) {
            return;
        }

        session()->flash('success', $this->attendanceCorrectionApprovals->approve($correction, Auth::user()));
        $this->dispatch('refresh');
    }

    public function rejectAttendanceCorrection($id)
    {
        $correction = AttendanceCorrection::find($id);

        if (! $correction || ! $this->isSubordinate($correction->user_id)) {
            return;
        }

        session()->flash('success', $this->attendanceCorrectionApprovals->reject($correction, Auth::user()));
        $this->dispatch('refresh');
    }

    public function approveShiftSwap($id)
    {
        $request = ShiftSwapRequest::find($id);

        if (! $request || ! $this->isSubordinate($request->user_id)) {
            return;
        }

        session()->flash('success', $this->shiftSwapApprovals->approve($request, Auth::user()));
        $this->dispatch('refresh');
    }

    public function rejectShiftSwap($id)
    {
        $request = ShiftSwapRequest::find($id);

        if (! $request || ! $this->isSubordinate($request->user_id)) {
            return;
        }

        session()->flash('success', $this->shiftSwapApprovals->reject($request, Auth::user()));
        $this->dispatch('refresh');
    }

    public function approveOvertime($id)
    {
        $overtime = Overtime::find($id);

        if (! $overtime || ! $this->isSubordinate($overtime->user_id)) {
            return;
        }

        $this->overtimeApprovals->approve($overtime, Auth::user());
        $this->dispatch('refresh');
        session()->flash('success', __('Overtime request approved.'));
    }

    public function rejectOvertime($id)
    {
        $overtime = Overtime::find($id);

        if (! $overtime || ! $this->isSubordinate($overtime->user_id)) {
            return;
        }

        $this->overtimeApprovals->reject($overtime, Auth::user());
        $this->dispatch('refresh');
        session()->flash('success', __('Overtime request rejected.'));
    }

    public function approveKasbon($id)
    {
        $advance = CashAdvance::find($id);

        if (! $advance || ! $this->isSubordinate($advance->user_id)) {
            return;
        }

        session()->flash('success', $this->cashAdvanceApprovals->approve($advance, Auth::user()));
        $this->dispatch('refresh');
    }

    public function rejectKasbon($id)
    {
        $advance = CashAdvance::find($id);

        if (! $advance || ! $this->isSubordinate($advance->user_id)) {
            return;
        }

        session()->flash('success', $this->cashAdvanceApprovals->reject($advance, Auth::user()));
        $this->dispatch('refresh');
    }

    public function updatingSearch(): void
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
        $result = $this->teamApprovalQueries->pending(Auth::user(), (string) $this->activeTab, (string) $this->search);

        match ($this->activeTab) {
            'attendance-corrections' => $attendanceCorrections = $result,
            'shift-swaps' => $shiftSwapRequests = $result,
            'reimbursements' => $reimbursements = $result,
            'overtimes' => $overtimes = $result,
            'kasbons' => $kasbons = $result,
            default => $leaves = $result,
        };

        return view('livewire.user.team-approvals', [
            'leaves' => $leaves,
            'attendanceCorrections' => $attendanceCorrections,
            'shiftSwapRequests' => $shiftSwapRequests,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
        ])->layout('layouts.app');
    }
}
