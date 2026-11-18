<?php

namespace App\Livewire\Finance\Concerns;

use App\Helpers\Editions;
use App\Models\CashAdvance;
use App\Models\User;
use App\Support\CashAdvanceApprovalService;
use Illuminate\Support\Facades\Auth;

trait ManagesCashAdvances
{
    public function mount()
    {
        if (Editions::payrollLocked()) {
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

        abort_unless($advance && $this->canManage($advance), 403);

        $message = app(CashAdvanceApprovalService::class)->approve($advance, Auth::user());

        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => $message,
        ]);
    }

    public function reject($id)
    {
        $advance = CashAdvance::find($id);

        abort_unless($advance && $this->canManage($advance), 403);

        $message = app(CashAdvanceApprovalService::class)->reject($advance, Auth::user());

        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => $message,
        ]);
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
        return app(CashAdvanceApprovalService::class)->canManage($advance, Auth::user());
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
                    $query->where('name', 'like', '%'.$this->search.'%');
                });
            }

            if (! $user->isAdmin && ! $user->isSuperadmin) {
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
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if (! $user->isAdmin && ! $user->isSuperadmin) {
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
