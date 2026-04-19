<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Notifications\LeaveStatusUpdated;

#[Layout('layouts.app')]
class LeaveApproval extends Component
{
    use WithPagination;

    private const LEGACY_REQUEST_STATUSES = ['sick', 'excused', 'permission', 'leave', 'rejected'];

    public string $search = '';
    public $rejectionNote;
    public $selectedIds = [];
    public $confirmingRejection = false;
    public $statusFilter = 'all';
    public string $requestTypeFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRequestTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Fetch requests based on filter
        $user = Auth::user();
        $query = Attendance::with(['user.division', 'user.jobTitle']);

        if ($this->statusFilter !== 'all') {
            $query->where('approval_status', $this->statusFilter);
        }

        if (!$user->isAdmin) {
             // Only subordinates
             $subordinateIds = $user->subordinates->pluck('id');
             $query->whereIn('user_id', $subordinateIds);
        }
        
        // Exclude 'present' records which are not requests (unless specifically looking for them? No, Leave Approval is for requests)
        // Usually requests have status 'sick', 'excused', 'permission', 'leave'.
        // We should probably filter out 'present' and 'late' to avoid clogging the list if 'all' is selected.
        $query->whereIn('status', self::LEGACY_REQUEST_STATUSES);

        if ($this->requestTypeFilter !== 'all') {
            $query->where('status', $this->requestTypeFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($subQuery) {
                $subQuery
                    ->where('note', 'like', '%' . $this->search . '%')
                    ->orWhere('rejection_note', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery
                            ->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('nip', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $allLeaves = $query->orderBy('date', 'desc')->get(); // Changed to desc for history

        // Group by User ID, Status, and Note to combine related requests
        $groupedLeaves = $allLeaves->groupBy(function ($item) {
            return $item->user_id . '|' . $item->status . '|' . $item->approval_status . '|' . trim($item->note);
        });

        return view('livewire.admin.leave-approval', [
            'groupedLeaves' => $groupedLeaves
        ]);
    }

    public function approve($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (count($this->authorizedRequestIds($ids)) !== count($ids)) {
            abort(403, 'Unauthorized action.');
        }

        Attendance::whereIn('id', $ids)->update([
            'approval_status' => Attendance::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $attendances = Attendance::whereIn('id', $ids)->get();
        foreach ($attendances as $attendance) {
            $attendance->user->notify(new LeaveStatusUpdated($attendance));
        }

        $this->dispatch('saved');
    }

    public function confirmReject($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $this->selectedIds = $ids;
        $this->confirmingRejection = true;
    }

    public function reject()
    {
        if (count($this->authorizedRequestIds($this->selectedIds)) !== count($this->selectedIds)) {
            abort(403, 'Unauthorized action.');
        }

        Attendance::whereIn('id', $this->selectedIds)->update([
            'approval_status' => Attendance::STATUS_REJECTED,
            'rejection_note' => $this->rejectionNote,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $attendances = Attendance::whereIn('id', $this->selectedIds)->get();
        foreach ($attendances as $attendance) {
            $attendance->user->notify(new LeaveStatusUpdated($attendance));
        }

        $this->confirmingRejection = false;
        $this->rejectionNote = '';
        $this->selectedIds = [];
        $this->dispatch('saved');
    }

    private function authorizedRequestIds(array $ids): array
    {
        $user = Auth::user();
        $query = Attendance::query()
            ->whereIn('id', $ids)
            ->whereIn('status', self::LEGACY_REQUEST_STATUSES);

        if ($user->isAdmin) {
            return $query->pluck('id')->toArray();
        }

        return $query
            ->whereIn('user_id', $user->subordinates->pluck('id'))
            ->pluck('id')
            ->toArray();
    }
}
