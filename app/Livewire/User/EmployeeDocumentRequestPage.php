<?php

namespace App\Livewire\User;

use App\Models\EmployeeDocumentRequest;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmployeeDocumentRequestPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showModal = false;

    public string $documentType = EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE;

    public string $purpose = '';

    public string $details = '';

    public $attachment;

    public ?int $uploadingRequestId = null;

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

    public function prepareUpload(int $requestId): void
    {
        $request = EmployeeDocumentRequest::findOrFail($requestId);
        $this->authorize('upload', $request);

        $this->uploadingRequestId = $request->id;
        $this->attachment = null;
    }

    public function cancelUpload(): void
    {
        $this->uploadingRequestId = null;
        $this->attachment = null;
    }

    public function upload(): void
    {
        if (! $this->uploadingRequestId) {
            return;
        }

        $request = EmployeeDocumentRequest::findOrFail($this->uploadingRequestId);
        $this->authorize('upload', $request);

        $this->validate([
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        session()->flash('success', $this->documentRequests->upload($request, Auth::user(), $this->attachment));
        $this->cancelUpload();
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
                ->with(['requester', 'reviewer', 'documentType'])
                ->where('user_id', Auth::id())
                ->latest()
                ->paginate(10),
            'documentTypes' => $this->documentRequests->activeDocumentTypeOptions(forEmployee: true),
        ])->layout('layouts.app');
    }
}
