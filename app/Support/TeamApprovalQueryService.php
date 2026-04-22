<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeamApprovalQueryService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {
    }

    public function pending(User $manager, string $tab, string $search = ''): LengthAwarePaginator
    {
        $subordinateIds = $this->approvalActors->subordinateIds($manager);

        return match ($tab) {
            'attendance-corrections' => AttendanceCorrection::query()
                ->with(['user', 'requestedShift'])
                ->whereIn('user_id', $subordinateIds)
                ->where('status', AttendanceCorrection::STATUS_PENDING)
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderByDesc('attendance_date')
                ->orderByDesc('created_at')
                ->paginate(10),
            'reimbursements' => Reimbursement::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'overtimes' => Overtime::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'kasbons' => CashAdvance::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            default => Attendance::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->where('approval_status', 'pending')
                ->where('status', '!=', 'present')
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        };
    }

    public function history(User $manager, string $tab, string $search = ''): LengthAwarePaginator
    {
        $subordinateIds = $this->approvalActors->subordinateIds($manager);

        return match ($tab) {
            'attendance-corrections' => AttendanceCorrection::query()
                ->with(['user', 'requestedShift', 'headApprover', 'reviewer'])
                ->whereIn('user_id', $subordinateIds)
                ->whereIn('status', [
                    AttendanceCorrection::STATUS_PENDING_ADMIN,
                    AttendanceCorrection::STATUS_APPROVED,
                    AttendanceCorrection::STATUS_REJECTED,
                ])
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderByDesc('updated_at')
                ->paginate(10),
            'reimbursements' => Reimbursement::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected', 'pending_finance'])
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('updated_at', 'desc')
                ->paginate(10),
            'overtimes' => Overtime::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected'])
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('updated_at', 'desc')
                ->paginate(10),
            'kasbons' => CashAdvance::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->whereIn('status', ['approved', 'rejected', 'paid', 'pending_finance'])
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('updated_at', 'desc')
                ->paginate(10),
            default => Attendance::query()
                ->with('user')
                ->whereIn('user_id', $subordinateIds)
                ->whereIn('approval_status', ['approved', 'rejected'])
                ->where('status', '!=', 'present')
                ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $search . '%')))
                ->orderBy('updated_at', 'desc')
                ->paginate(10),
        };
    }
}
