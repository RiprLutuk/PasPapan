<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Auth;

class CashAdvanceManager extends Component
{
    use WithPagination;

    public $statusFilter = 'pending';

    public function mount()
    {
        if (\App\Helpers\Editions::payrollLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Kasbon Locked', 'message' => 'Manage Kasbon is an Enterprise Feature 🔒. Please Upgrade.']);
            return redirect()->route(Auth::user()->isAdmin ? 'admin.dashboard' : 'home');
        }
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

        // Manager / Head Logic
        $myRank = $user->jobTitle?->jobLevel?->rank;
        if (!$myRank || $myRank > 2) return false;

        $targetRank = $advance->user->jobTitle?->jobLevel?->rank;

        // Ensure the manager's rank is numerically lower (1 is higher than 2) than the target's rank
        return $targetRank && $myRank < $targetRank;
    }

    public function render()
    {
        $user = Auth::user();
        $query = CashAdvance::with(['user.jobTitle.jobLevel', 'approver']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (!$user->isAdmin && !$user->isSuperadmin) {
            // Manager / Head logic to only see subordinates based on rank
            $myRank = $user->jobTitle?->jobLevel?->rank;
            if ($myRank && $myRank <= 2) {
                $query->whereHas('user.jobTitle.jobLevel', function ($q) use ($myRank) {
                    $q->where('rank', '>', $myRank);
                });
            } else {
                // Regular user trying to access manager page
                $query->where('id', 0); // show nothing
            }
        }

        $advances = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.finance.cash-advance-manager', [
            'advances' => $advances
        ])->layout('layouts.app');
    }
}
