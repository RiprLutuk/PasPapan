<?php

namespace App\Livewire\Admin;

use App\Models\ShiftSwapRequest;
use App\Support\ShiftSwapRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ShiftSwapApprovalManager extends Component
{
    use WithPagination;

    protected ShiftSwapRequestService $shiftSwapRequests;

    public string $search = '';

    public string $statusFilter = ShiftSwapRequest::STATUS_PENDING;

    public ?int $selectedId = null;

    public bool $confirmingRejection = false;

    public string $rejectionNote = '';

    public function boot(ShiftSwapRequestService $shiftSwapRequests): void
    {
        Gate::authorize('manageShiftSwapApprovals');

        $this->shiftSwapRequests = $shiftSwapRequests;
    }

    public function render()
    {
        $requests = $this->shiftSwapRequests
            ->managementQuery(Auth::user(), $this->statusFilter, $this->search)
            ->paginate(15);

        return view('livewire.admin.shift-swap-approval-manager', [
            'requests' => $requests,
            'statuses' => ShiftSwapRequest::statuses(),
        ]);
    }

    public function approve(int $id): void
    {
        $request = ShiftSwapRequest::query()->findOrFail($id);
        $message = $this->shiftSwapRequests->approve($request, Auth::user());

        $request->refresh();

        $this->dispatch(
            'toast',
            type: $request->status === ShiftSwapRequest::STATUS_APPROVED ? 'success' : 'danger',
            message: $message,
        );
    }

    public function confirmReject(int $id): void
    {
        $this->selectedId = $id;
        $this->confirmingRejection = true;
    }

    public function reject(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $this->validate([
            'rejectionNote' => ['nullable', 'string', 'max:500'],
        ]);

        $request = ShiftSwapRequest::query()->findOrFail($this->selectedId);
        $message = $this->shiftSwapRequests->reject($request, Auth::user(), $this->rejectionNote ?: null);

        $request->refresh();
        $this->cancelReject();

        $this->dispatch(
            'toast',
            type: $request->status === ShiftSwapRequest::STATUS_REJECTED ? 'success' : 'danger',
            message: $message,
        );
    }

    public function cancelReject(): void
    {
        $this->confirmingRejection = false;
        $this->rejectionNote = '';
        $this->selectedId = null;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
