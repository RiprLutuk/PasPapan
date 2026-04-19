<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Notifications\OvertimeStatusUpdated;

#[Layout('layouts.app')]
class OvertimeManager extends Component
{
    use WithPagination;

    public string $search = '';
    public $rejectionReason;
    public $selectedId = null;
    public $confirmingRejection = false;
    public $statusFilter = 'pending';

    public function render()
    {
        $query = Overtime::with(['user.division', 'approvedBy']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($subQuery) {
                $subQuery
                    ->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('rejection_reason', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery
                            ->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('nip', 'like', '%' . $this->search . '%')
                            ->orWhereHas('division', function ($divisionQuery) {
                                $divisionQuery->where('name', 'like', '%' . $this->search . '%');
                            });
                    });
            });
        }

        $overtimes = $query->orderBy('date', 'desc')->paginate(15);

        return view('livewire.admin.overtime-manager', [
            'overtimes' => $overtimes
        ]);
    }

    public function approve($id)
    {
        $overtime = Overtime::findOrFail($id);
        
        $overtime->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        // Send notification
        if (class_exists(OvertimeStatusUpdated::class)) {
            $overtime->user->notify(new OvertimeStatusUpdated($overtime));
        }

        $this->dispatch('toast', type: 'success', message: __('Overtime approved.'));
    }

    public function confirmReject($id)
    {
        $this->selectedId = $id;
        $this->confirmingRejection = true;
    }

    public function reject()
    {
        if (!$this->selectedId) return;

        $overtime = Overtime::findOrFail($this->selectedId);

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejection_reason' => $this->rejectionReason,
        ]);

        // Send notification
        if (class_exists(OvertimeStatusUpdated::class)) {
            $overtime->user->notify(new OvertimeStatusUpdated($overtime));
        }

        $this->confirmingRejection = false;
        $this->rejectionReason = '';
        $this->selectedId = null;

        $this->dispatch('toast', type: 'success', message: __('Overtime rejected.'));
    }

    public function cancelReject()
    {
        $this->confirmingRejection = false;
        $this->rejectionReason = '';
        $this->selectedId = null;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
