<?php

namespace App\Livewire\User;

use App\Models\EmployeeDocumentRequest;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeDocumentRequestPage extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $documentType = EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE;

    public string $purpose = '';

    public string $details = '';

    protected EmployeeDocumentRequestService $documentRequests;

    public function boot(EmployeeDocumentRequestService $documentRequests): void
    {
        $this->documentRequests = $documentRequests;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', EmployeeDocumentRequest::class);
    }

    public function create(): void
    {
        $this->authorize('create', EmployeeDocumentRequest::class);
        $this->reset(['purpose', 'details']);
        $this->documentType = EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE;
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
    }

    public function store(): void
    {
        $this->authorize('create', EmployeeDocumentRequest::class);

        $validated = $this->validate([
            'documentType' => ['required', Rule::in(array_keys(EmployeeDocumentRequest::documentTypes()))],
            'purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->documentRequests->submit(Auth::user(), [
            'document_type' => $validated['documentType'],
            'purpose' => $validated['purpose'],
            'details' => $validated['details'] ?? null,
        ]);

        $this->showModal = false;
        $this->reset(['purpose', 'details']);
        session()->flash('success', __('Document request submitted successfully.'));
    }

    public function render()
    {
        $this->authorize('viewAny', EmployeeDocumentRequest::class);

        return view('livewire.user.employee-document-request-page', [
            'requests' => EmployeeDocumentRequest::query()
                ->with('reviewer')
                ->where('user_id', Auth::id())
                ->latest()
                ->paginate(10),
            'documentTypes' => EmployeeDocumentRequest::documentTypes(),
        ])->layout('layouts.app');
    }
}
