<?php

namespace App\Support;

use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\ReimbursementStatusUpdated;
use Illuminate\Database\Eloquent\Builder;

class ReimbursementApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    public function approve(Reimbursement $reimbursement, User $actor): string
    {
        if ($this->approvalActors->canFinalizeReimbursementApproval($actor)) {
            $reimbursement->update([
                'status' => 'approved',
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
                'approved_by' => $actor->id,
            ]);

            $this->notifyStatusUpdated($reimbursement);

            return __('Reimbursement approved.');
        }

        $reimbursement->update([
            'status' => 'pending_finance',
            'head_approved_by' => $actor->id,
            'head_approved_at' => now(),
        ]);

        $this->notifyStatusUpdated($reimbursement);

        return __('Reimbursement forwarded to Finance for final approval.');
    }

    public function reject(Reimbursement $reimbursement, User $actor): string
    {
        $payload = [
            'status' => 'rejected',
        ];

        if ($this->approvalActors->canFinalizeReimbursementApproval($actor)) {
            $payload += [
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
                'approved_by' => $actor->id,
            ];
        } else {
            $payload += [
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ];
        }

        $reimbursement->update($payload);
        $this->notifyStatusUpdated($reimbursement);

        return __('Reimbursement rejected.');
    }

    public function managementQuery(User $actor, string $statusFilter = 'pending', string $search = ''): Builder
    {
        $subordinateIds = $this->approvalActors->subordinateIds($actor);
        $canFinalize = $this->approvalActors->canFinalizeReimbursementApproval($actor);

        return Reimbursement::query()
            ->with(['user', 'approvedBy', 'headApprover', 'financeApprover'])
            ->when(! $actor->can('viewAdminReimbursements'), function (Builder $query) use ($canFinalize, $subordinateIds) {
                if ($canFinalize) {
                    return $query->where(function (Builder $nested) use ($subordinateIds) {
                        $nested->where('status', 'pending_finance')
                            ->orWhereIn('user_id', $subordinateIds);
                    });
                }

                return $query->whereIn('user_id', $subordinateIds);
            })
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
            })
            ->latest();
    }

    protected function notifyStatusUpdated(Reimbursement $reimbursement): void
    {
        $reimbursement->loadMissing('user');
        $reimbursement->user?->notify(new ReimbursementStatusUpdated($reimbursement));
    }
}
