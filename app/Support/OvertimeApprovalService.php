<?php

namespace App\Support;

use App\Models\Overtime;
use App\Models\User;
use App\Notifications\OvertimeStatusUpdated;
use Illuminate\Database\Eloquent\Builder;

class OvertimeApprovalService
{
    public function managementQuery(string $statusFilter = 'pending', string $search = ''): Builder
    {
        return Overtime::query()
            ->with(['user.division', 'approvedBy'])
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery
                        ->where('reason', 'like', '%'.$search.'%')
                        ->orWhere('rejection_reason', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%')
                                ->orWhereHas('division', fn (Builder $divisionQuery) => $divisionQuery->where('name', 'like', '%'.$search.'%'));
                        });
                });
            })
            ->orderBy('date', 'desc');
    }

    public function approve(Overtime $overtime, User $actor): void
    {
        $overtime->update([
            'status' => 'approved',
            'approved_by' => $actor->id,
        ]);

        $this->notifyStatusUpdated($overtime);
    }

    public function reject(Overtime $overtime, User $actor, ?string $rejectionReason = null): void
    {
        $overtime->update([
            'status' => 'rejected',
            'approved_by' => $actor->id,
            'rejection_reason' => $rejectionReason,
        ]);

        $this->notifyStatusUpdated($overtime);
    }

    protected function notifyStatusUpdated(Overtime $overtime): void
    {
        if (! class_exists(OvertimeStatusUpdated::class)) {
            return;
        }

        $overtime->loadMissing('user');
        $overtime->user?->notify(new OvertimeStatusUpdated($overtime));
    }
}
