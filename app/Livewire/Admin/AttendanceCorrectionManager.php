<?php

namespace App\Livewire\Admin;

use App\Models\AttendanceCorrection;
use App\Support\AttendanceCorrectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AttendanceCorrectionManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected AttendanceCorrectionService $correctionService;

    public string $statusFilter = 'pending_admin';
    public string $typeFilter = 'all';
    public string $search = '';
    public ?int $selectedId = null;
    public string $rejectionNote = '';
    public bool $confirmingRejection = false;

    public function boot(AttendanceCorrectionService $correctionService): void
    {
        $this->correctionService = $correctionService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $correction = AttendanceCorrection::findOrFail($id);
        $this->authorize('approve', $correction);

        session()->flash('success', $this->correctionService->approve($correction, auth()->user()));
    }

    public function confirmReject(int $id): void
    {
        $this->selectedId = $id;
        $this->rejectionNote = '';
        $this->confirmingRejection = true;
    }

    public function reject(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $correction = AttendanceCorrection::findOrFail($this->selectedId);
        $this->authorize('reject', $correction);

        session()->flash('success', $this->correctionService->reject($correction, auth()->user(), $this->rejectionNote ?: null));

        $this->confirmingRejection = false;
        $this->selectedId = null;
        $this->rejectionNote = '';
    }

    public function cancelReject(): void
    {
        $this->confirmingRejection = false;
        $this->selectedId = null;
        $this->rejectionNote = '';
    }

    public function render()
    {
        $this->authorize('viewAdminAny', AttendanceCorrection::class);

        $corrections = $this->correctionService
            ->managementQuery(auth()->user(), $this->statusFilter, $this->typeFilter, $this->search)
            ->paginate(12);

        return view('livewire.admin.attendance-correction-manager', [
            'corrections' => $corrections,
            'requestTypes' => AttendanceCorrection::requestTypes(),
        ]);
    }
}
