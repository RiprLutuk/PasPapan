<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class LeaveApproval extends Component
{
    use WithPagination;

    public $rejectionNote;
    public $selectedId;
    public $confirmingRejection = false;

    public function render()
    {
        $leaves = Attendance::pending()
            ->with('user')
            ->orderBy('date', 'asc')
            ->paginate(10);

        return view('livewire.admin.leave-approval', [
            'leaves' => $leaves
        ])->layout('layouts.app');
    }

    public function approve($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->update([
            'approval_status' => Attendance::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->dispatch('saved'); 
        // In the future: Notify user
    }

    public function confirmReject($id)
    {
        $this->selectedId = $id;
        $this->confirmingRejection = true;
    }

    public function reject()
    {
        $attendance = Attendance::findOrFail($this->selectedId);
        $attendance->update([
            'approval_status' => Attendance::STATUS_REJECTED,
            'rejection_note' => $this->rejectionNote,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->confirmingRejection = false;
        $this->rejectionNote = '';
        $this->dispatch('saved');
        // In the future: Notify user
    }
}
