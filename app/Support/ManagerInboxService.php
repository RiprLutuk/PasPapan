<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\EmployeeDocumentRequest;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ManagerInboxService
{
    /**
     * @return list<string>
     */
    public function accessibleTabs(User $admin): array
    {
        $tabs = [];

        if ($admin->can('manageLeaveApprovals')) {
            $tabs[] = 'leaves';
        }

        if ($admin->can('manageOvertime')) {
            $tabs[] = 'overtime';
        }

        if ($admin->can('viewAdminAny', AttendanceCorrection::class)) {
            $tabs[] = 'attendance_corrections';
        }

        if ($admin->can('viewAdminAny', Reimbursement::class)) {
            $tabs[] = 'reimbursements';
        }

        if ($admin->can('manageCashAdvances')) {
            $tabs[] = 'cash_advances';
        }

        if ($admin->can('manageShiftSwapApprovals')) {
            $tabs[] = 'shift_swaps';
        }

        if ($admin->can('viewAdminAny', EmployeeDocumentRequest::class)) {
            $tabs[] = 'document_requests';
        }

        return $tabs;
    }

    /**
     * Get counts of pending requests for the admin inbox.
     * Respects the admin's managed scope.
     */
    public function getPendingCounts(User $admin): array
    {
        $allowedTabs = array_flip($this->accessibleTabs($admin));

        $counts = [
            'leaves' => Attendance::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('approval_status', 'pending')
                ->whereNotNull('leave_type_id')
                ->count(),

            'overtime' => Overtime::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->count(),

            'attendance_corrections' => AttendanceCorrection::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN])
                ->count(),

            'reimbursements' => Reimbursement::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->count(),

            'cash_advances' => CashAdvance::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->count(),

            'shift_swaps' => ShiftSwapRequest::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', ShiftSwapRequest::STATUS_PENDING)
                ->count(),

            'document_requests' => EmployeeDocumentRequest::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [EmployeeDocumentRequest::STATUS_PENDING, EmployeeDocumentRequest::STATUS_REQUESTED])
                ->count(),
        ];

        return array_intersect_key($counts, $allowedTabs);
    }

    /**
     * Get total pending count for badge.
     */
    public function getTotalPendingCount(User $admin): int
    {
        return array_sum($this->getPendingCounts($admin));
    }
}
