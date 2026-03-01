<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use App\Models\User;

class CashAdvanceManager extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $activeTab = 'requests'; // requests, users

    public $statusFilter = 'pending';
    public $search = '';

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Kasbon Locked', 'message' => 'Manage Kasbon is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route(Auth::user()->isAdmin ? 'admin.dashboard' : 'home');
        }
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
            $advance->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
            $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

            $this->dispatch('banner-message', [
                'style' => 'success',
                'message' => 'Kasbon disetujui.'
            ]);
        }
    }

    public function reject($id)
    {
        $advance = CashAdvance::find($id);
        if ($advance && $this->canManage($advance)) {
            $advance->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $advance->user->notify(new \App\Notifications\CashAdvanceUpdated($advance));
            $advance->user->notify(new \App\Notifications\CashAdvanceUpdatedEmail($advance));

            $this->dispatch('banner-message', [
                'style' => 'success',
                'message' => 'Kasbon ditolak.'
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
                'message' => 'Data Kasbon dihapus.'
            ]);
        } else {
            $this->dispatch('banner-message', [
                'style' => 'danger',
                'message' => 'Hanya Admin yang dapat menghapus data.'
            ]);
        }
    }

    protected function canManage($advance)
    {
        $user = Auth::user();
        if ($user->isAdmin || $user->isSuperadmin) return true;

        $myRank = $user->jobTitle?->jobLevel?->rank;
        if (!$myRank || $myRank > 2) return false;

        $targetRank = $advance->user->jobTitle?->jobLevel?->rank;
        return $targetRank && $myRank < $targetRank;
    }

    public function render()
    {
        $user = Auth::user();

        if ($this->activeTab === 'requests') {
            $query = CashAdvance::with(['user.jobTitle.jobLevel', 'approver']);

            if ($this->statusFilter !== 'all') {
                $query->where('status', $this->statusFilter);
            }

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            if (!$user->isAdmin && !$user->isSuperadmin) {
                $myRank = $user->jobTitle?->jobLevel?->rank;
                if ($myRank && $myRank <= 2) {
                    $query->whereHas('user.jobTitle.jobLevel', function ($q) use ($myRank) {
                        $q->where('rank', '>', $myRank);
                    });
                } else {
                    $query->where('id', 0);
                }
            }

            return view('livewire.finance.cash-advance-manager', [
                'advances' => $query->orderBy('created_at', 'desc')->paginate(10),
                'userGrouped' => collect()
            ])->layout('layouts.app');
        } else {
            // "users" tab (Grouped by User and Month)
            $query = User::with(['jobTitle', 'cashAdvances' => function ($q) {
                // only count approved or paid kasbons for the summary
                $q->whereIn('status', ['approved', 'paid', 'pending', 'rejected']);
            }])->whereHas('cashAdvances');

            if ($this->search) {
                $query->where('name', 'like', '%' . $this->search . '%');
            }

            if (!$user->isAdmin && !$user->isSuperadmin) {
                $myRank = $user->jobTitle?->jobLevel?->rank;
                if ($myRank && $myRank <= 2) {
                    $query->whereHas('jobTitle.jobLevel', function ($q) use ($myRank) {
                        $q->where('rank', '>', $myRank);
                    });
                } else {
                    $query->where('id', 0);
                }
            }

            return view('livewire.finance.cash-advance-manager', [
                'advances' => collect(),
                'userGrouped' => $query->paginate(10)
            ])->layout('layouts.app');
        }
    }
}
