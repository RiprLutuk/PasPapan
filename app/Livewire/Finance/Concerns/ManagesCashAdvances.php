<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\CashAdvance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait ManagesCashAdvances
{
    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
            session()->flash('show-feature-lock', [
                'title' => 'Kasbon Locked',
                'message' => 'Manage Kasbon is an Enterprise Feature 🔒. Please Upgrade.',
            ]);

            return redirect()->route($this->lockedRedirectRoute());
        }
    }

    protected function lockedRedirectRoute(): string
    {
        return 'home';
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function approve($id)
    {
        $advance = CashAdvance::find($id);

        if ($advance && $this->canManage($advance)) {
            $user = Auth::user();
            $isFinanceHead = $user->isAdmin
                || $user->isSuperadmin
                || ($user->jobTitle?->jobLevel?->rank <= 2
                    && $user->division
                    && strtolower($user->division->name) === 'finance');

            if ($isFinanceHead) {
                $advance->update([
                    'status' => 'approved',
                    'finance_approved_by' => $user->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
                $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

                $this->dispatch('banner-message', [
                    'style' => 'success',
                    'message' => 'Kasbon disetujui sepenuhnya.',
                ]);
            } else {
                $advance->update([
                    'status' => 'pending_finance',
                    'head_approved_by' => $user->id,
                    'head_approved_at' => now(),
                ]);

                $this->dispatch('banner-message', [
                    'style' => 'success',
                    'message' => 'Kasbon disetujui, menunggu persetujuan Finance.',
                ]);
            }
        }
    }

    public function reject($id)
    {
        $advance = CashAdvance::find($id);

        if ($advance && $this->canManage($advance)) {
            $user = Auth::user();
            $isFinanceHead = $user->isAdmin
                || $user->isSuperadmin
                || ($user->jobTitle?->jobLevel?->rank <= 2
                    && $user->division
                    && strtolower($user->division->name) === 'finance');

            $advance->update([
                'status' => 'rejected',
            ]);

            if ($isFinanceHead) {
                $advance->update([
                    'finance_approved_by' => $user->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
            } else {
                $advance->update([
                    'head_approved_by' => $user->id,
                    'head_approved_at' => now(),
                ]);
            }

            $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
            $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

            $this->dispatch('banner-message', [
                'style' => 'success',
                'message' => 'Kasbon ditolak.',
            ]);
        }
    }

    public function delete($id)
    {
        $advance = CashAdvance::find($id);

        if ($advance && Auth::user()->isAdmin) {
            $advance->delete();

            $this->dispatch('banner-message', [
                'style' => 'success',
                'message' => 'Data Kasbon dihapus.',
            ]);

            return;
        }

        $this->dispatch('banner-message', [
            'style' => 'danger',
            'message' => 'Hanya Admin yang dapat menghapus data.',
        ]);
    }

    protected function canManage($advance)
    {
        $user = Auth::user();

        if ($user->isAdmin || $user->isSuperadmin) {
            return true;
        }

        $myRank = $user->jobTitle?->jobLevel?->rank;
        $myDivisionId = $user->division_id;

        if (!$myRank || $myRank > 2) {
            return false;
        }

        $isFinanceHead = $myRank <= 2
            && $user->division
            && strtolower($user->division->name) === 'finance';

        if ($isFinanceHead) {
            if ($advance->status === 'pending_finance') {
                return true;
            }

            if (
                $advance->user->division_id === $myDivisionId
                && $advance->user->jobTitle?->jobLevel?->rank > $myRank
            ) {
                return true;
            }
        } elseif (
            $advance->user->division_id === $myDivisionId
            && $advance->user->jobTitle?->jobLevel?->rank > $myRank
            && $advance->status === 'pending'
        ) {
            return true;
        }

        return false;
    }

    protected function cashAdvanceViewData(): array
    {
        $user = Auth::user();

        if ($this->activeTab === 'requests') {
            $query = CashAdvance::with([
                'user.jobTitle.jobLevel',
                'user.kabupaten',
                'approver',
                'headApprover',
                'financeApprover',
            ]);

            if ($this->statusFilter !== 'all') {
                $query->where('status', $this->statusFilter);
            }

            if ($this->search) {
                $query->whereHas('user', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            }

            if (!$user->isAdmin && !$user->isSuperadmin) {
                $myRank = $user->jobTitle?->jobLevel?->rank;
                $myDivisionId = $user->division_id;

                if ($myRank && $myRank <= 2) {
                    $isFinanceHead = $user->division
                        && strtolower($user->division->name) === 'finance';

                    if ($isFinanceHead) {
                        $query->where(function ($query) use ($myRank, $myDivisionId) {
                            $query->where('status', 'pending_finance')
                                ->orWhereHas('user', function ($userQuery) use ($myRank, $myDivisionId) {
                                    $userQuery->where('division_id', $myDivisionId)
                                        ->whereHas('jobTitle.jobLevel', function ($levelQuery) use ($myRank) {
                                            $levelQuery->where('rank', '>', $myRank);
                                        });
                                });
                        });
                    } else {
                        $query->whereHas('user', function ($userQuery) use ($myRank, $myDivisionId) {
                            $userQuery->where('division_id', $myDivisionId)
                                ->whereHas('jobTitle.jobLevel', function ($levelQuery) use ($myRank) {
                                    $levelQuery->where('rank', '>', $myRank);
                                });
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

        $query = User::with([
            'jobTitle',
            'kabupaten',
            'cashAdvances' => function ($query) {
                $query->whereIn('status', ['approved', 'paid', 'pending', 'rejected', 'pending_finance']);
            },
        ])->whereHas('cashAdvances');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if (!$user->isAdmin && !$user->isSuperadmin) {
            $myRank = $user->jobTitle?->jobLevel?->rank;

            if ($myRank && $myRank <= 2) {
                $query->whereHas('jobTitle.jobLevel', function ($query) use ($myRank) {
                    $query->where('rank', '>', $myRank);
                });
            } else {
                $query->where('id', 0);
            }
        }

        return [
            'advances' => collect(),
            'userGrouped' => $query->paginate(10),
        ];
    }
}
