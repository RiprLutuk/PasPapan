<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeDocumentRequest;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class EmployeeDocumentRequestManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected EmployeeDocumentRequestService $documentRequests;

    public string $statusFilter = EmployeeDocumentRequest::STATUS_PENDING;

    public string $typeFilter = 'all';

    public string $search = '';

    public ?int $selectedId = null;

    public string $reviewNote = '';

    public bool $confirmingReady = false;

    public bool $confirmingRejection = false;

    public function boot(EmployeeDocumentRequestService $documentRequests): void
    {
        $this->documentRequests = $documentRequests;
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

    public function confirmReady(int $id): void
    {
        $this->selectedId = $id;
        $this->reviewNote = '';
        $this->confirmingReady = true;
    }

    public function markReady(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $request = EmployeeDocumentRequest::findOrFail($this->selectedId);
        $this->authorize('fulfill', $request);

        session()->flash('success', $this->documentRequests->markReady($request, auth()->user(), $this->reviewNote ?: null));

        $this->resetReviewDialog();
    }

    public function confirmReject(int $id): void
    {
        $this->selectedId = $id;
        $this->reviewNote = '';
        $this->confirmingRejection = true;
    }

    public function reject(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $request = EmployeeDocumentRequest::findOrFail($this->selectedId);
        $this->authorize('reject', $request);

        session()->flash('success', $this->documentRequests->reject($request, auth()->user(), $this->reviewNote ?: null));

        $this->resetReviewDialog();
    }

    public function cancelReview(): void
    {
        $this->resetReviewDialog();
    }

    public function render()
    {
        $this->authorize('viewAdminAny', EmployeeDocumentRequest::class);

        return view('livewire.admin.employee-document-request-manager', [
            'requests' => $this->documentRequests
                ->managementQuery($this->statusFilter, $this->typeFilter, $this->search)
                ->paginate(12),
            'documentTypes' => EmployeeDocumentRequest::documentTypes(),
            'statuses' => EmployeeDocumentRequest::statuses(),
        ]);
    }

    private function resetReviewDialog(): void
    {
        $this->selectedId = null;
        $this->reviewNote = '';
        $this->confirmingReady = false;
        $this->confirmingRejection = false;
    }
}
