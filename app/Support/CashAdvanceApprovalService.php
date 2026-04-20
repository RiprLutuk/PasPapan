<?php

namespace App\Support;

use App\Models\CashAdvance;
use App\Models\User;
use App\Notifications\CashAdvanceUpdated;
use App\Notifications\CashAdvanceUpdatedEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CashAdvanceApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {
    }

    public function approve(CashAdvance $advance, User $actor): string
    {
        if ($this->approvalActors->canFinalizeFinanceApproval($actor)) {
            $advance->update([
                'status' => 'approved',
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->notifyStatusUpdated($advance);

            return __('Kasbon approved.');
        }

        $advance->update([
            'status' => 'pending_finance',
            'head_approved_by' => $actor->id,
            'head_approved_at' => now(),
        ]);

        $this->notifyStatusUpdated($advance);

        return __('Kasbon forwarded to Finance for final approval.');
    }

    public function reject(CashAdvance $advance, User $actor): string
    {
        $payload = [
            'status' => 'rejected',
        ];

        if ($this->approvalActors->canFinalizeFinanceApproval($actor)) {
            $payload += [
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ];
        } else {
            $payload += [
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ];
        }

        $advance->update($payload);
        $this->notifyStatusUpdated($advance);

        return __('Kasbon rejected.');
    }

    public function canManage(CashAdvance $advance, User $user): bool
    {
        if ($user->isAdmin || $user->isSuperadmin) {
            return true;
        }

        $myRank = $user->jobTitle?->jobLevel?->rank;
        $myDivisionId = $user->division_id;

        if (! $myRank || $myRank > 2) {
            return false;
        }

        if ($this->approvalActors->isFinanceHead($user)) {
            if ($advance->status === 'pending_finance') {
                return true;
            }

            return $advance->user->division_id === $myDivisionId
                && $advance->user->jobTitle?->jobLevel?->rank > $myRank;
        }

        return $advance->user->division_id === $myDivisionId
            && $advance->user->jobTitle?->jobLevel?->rank > $myRank
            && $advance->status === 'pending';
    }

    /**
     * @return array{advances: \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection, userGrouped: \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection}
     */
    public function managementViewData(User $user, string $activeTab, string $statusFilter = 'all', string $search = ''): array
    {
        if ($activeTab === 'requests') {
            $query = CashAdvance::query()->with([
                'user.jobTitle.jobLevel',
                'user.kabupaten',
                'approver',
                'headApprover',
                'financeApprover',
            ]);

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            if ($search !== '') {
                $query->whereHas('user', fn (Builder $builder) => $builder->where('name', 'like', '%' . $search . '%'));
            }

            if (! $user->isAdmin && ! $user->isSuperadmin) {
                $myRank = $user->jobTitle?->jobLevel?->rank;
                $myDivisionId = $user->division_id;

                if ($myRank && $myRank <= 2) {
                    if ($this->approvalActors->isFinanceHead($user)) {
                        $query->where(function (Builder $builder) use ($myDivisionId, $myRank) {
                            $builder->where('status', 'pending_finance')
                                ->orWhereHas('user', function (Builder $userQuery) use ($myDivisionId, $myRank) {
                                    $userQuery->where('division_id', $myDivisionId)
                                        ->whereHas('jobTitle.jobLevel', fn (Builder $levelQuery) => $levelQuery->where('rank', '>', $myRank));
                                });
                        });
                    } else {
                        $query->whereHas('user', function (Builder $userQuery) use ($myDivisionId, $myRank) {
                            $userQuery->where('division_id', $myDivisionId)
                                ->whereHas('jobTitle.jobLevel', fn (Builder $levelQuery) => $levelQuery->where('rank', '>', $myRank));
                        });
                    }
                } else {
                    $query->where('id', 0);
                }
            }

            return [
                'advances' => $query->orderBy('created_at', 'desc')->paginate(10),
                'userGrouped' => collect(),
            ];
        }

        $query = User::query()->with([
            'jobTitle',
            'kabupaten',
            'cashAdvances' => fn (Builder $builder) => $builder->whereIn('status', ['approved', 'paid', 'pending', 'pending_finance', 'rejected']),
        ])->whereHas('cashAdvances');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (! $user->isAdmin && ! $user->isSuperadmin) {
            $myRank = $user->jobTitle?->jobLevel?->rank;

            if ($myRank && $myRank <= 2) {
                $query->whereHas('jobTitle.jobLevel', fn (Builder $builder) => $builder->where('rank', '>', $myRank));
            } else {
                $query->where('id', 0);
            }
        }

        return [
            'advances' => collect(),
            'userGrouped' => $query->paginate(10),
        ];
    }

    protected function notifyStatusUpdated(CashAdvance $advance): void
    {
        $advance->loadMissing('user');

        if (! $advance->user) {
            return;
        }

        $advance->user->notify(new CashAdvanceUpdated($advance));
        $advance->user->notify(new CashAdvanceUpdatedEmail($advance));
    }
}
