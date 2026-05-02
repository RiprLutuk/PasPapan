<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
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

    public bool $showCreateModal = false;

    public string $targetUserId = '';

    public array $targetUserIds = [];

    public string $documentType = EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE;

    public string $purpose = '';

    public string $details = '';

    public string $dueDate = '';

    public bool $generateImmediately = false;

    public array $selectedRequestIds = [];

    public bool $selectAll = false;

    public function boot(EmployeeDocumentRequestService $documentRequests): void
    {
        $this->documentRequests = $documentRequests;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedRequestIds = $value ? $this->currentPageRequestIds() : [];
    }

    public function confirmReady(int $id): void
    {
        $this->selectedId = $id;
        $this->reviewNote = '';
        $this->confirmingReady = true;
    }

    public function createRequest(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function storeRequest(): void
    {
        $this->authorize('createForEmployee', EmployeeDocumentRequest::class);

        $documentTypes = array_values(array_unique(array_merge(
            array_keys($this->documentRequests->activeDocumentTypeOptions(forAdmin: true)),
            array_column($this->documentRequests->defaultDocumentTypes(), 'code'),
        )));
        $validated = $this->validate([
            'targetUserIds' => ['array'],
            'targetUserIds.*' => ['exists:users,id'],
            'targetUserId' => ['nullable', 'exists:users,id'],
            'documentType' => ['required', Rule::in($documentTypes)],
            'purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'details' => ['nullable', 'string', 'max:2000'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:today'],
            'generateImmediately' => ['boolean'],
        ]);

        $targetIds = collect($validated['targetUserIds'] ?? [])
            ->when($this->targetUserId !== '', fn ($ids) => $ids->push($this->targetUserId))
            ->filter()
            ->unique()
            ->values();

        if ($targetIds->isEmpty()) {
            $this->addError('targetUserIds', __('Choose at least one employee.'));

            return;
        }

        $targets = User::query()
            ->where('group', 'user')
            ->whereIn('id', $targetIds)
            ->get();

        $created = 0;
        $generated = 0;

        foreach ($targets as $target) {
            $request = $this->documentRequests->requestFromAdmin(auth()->user(), $target, [
                'document_type' => $validated['documentType'],
                'purpose' => $validated['purpose'],
                'details' => $validated['details'] ?? null,
                'due_date' => $validated['dueDate'] ?? null,
            ]);
            $created++;

            if ($validated['generateImmediately'] ?? false) {
                $this->authorize('generate', $request);
                $this->documentRequests->generate($request, auth()->user());
                $generated++;
            }
        }

        session()->flash('success', $generated > 0
            ? __('Created :created request(s) and generated :generated document(s).', ['created' => $created, 'generated' => $generated])
            : __('Created :count document request(s).', ['count' => $created]));
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function applyRequestPreset(): void
    {
        $profile = $this->selectedDocumentTypeProfile();

        if (! $profile) {
            return;
        }

        if ($profile->requires_employee_upload) {
            $this->purpose = __('Please upload :document for HR/Finance administration.', [
                'document' => $profile->name,
            ]);
            $this->details = __('Upload a clear and valid file. PDF or image format is preferred.');
            $this->dueDate = $this->dueDate ?: now()->addWeek()->toDateString();

            return;
        }

        $this->purpose = __('Please prepare :document for employee administration.', [
            'document' => $profile->name,
        ]);
        $this->details = __('Include active employment status, job title, division, and the requested purpose when relevant.');
        $this->dueDate = $this->dueDate ?: now()->addDays(3)->toDateString();
    }

    public function updatedDocumentType(): void
    {
        $profile = $this->selectedDocumentTypeProfile();
        $this->generateImmediately = (bool) ($profile?->auto_generate_enabled && $profile?->activeTemplate());
    }

    public function setDueDatePreset(int $days): void
    {
        if ($days < 0 || $days > 365) {
            return;
        }

        $this->dueDate = now()->addDays($days)->toDateString();
    }

    public function clearDueDate(): void
    {
        $this->dueDate = '';
    }

    public function generate(int $id): void
    {
        $request = EmployeeDocumentRequest::findOrFail($id);
        $this->authorize('generate', $request);

        session()->flash('success', $this->documentRequests->generate($request, auth()->user()));
    }

    public function bulkGenerate(): void
    {
        $count = 0;

        foreach ($this->selectedRequests() as $request) {
            if (! auth()->user()->can('generate', $request)) {
                continue;
            }

            $this->documentRequests->generate($request, auth()->user());
            $count++;
        }

        $this->clearSelection();
        session()->flash('success', __('Generated :count selected document(s).', ['count' => $count]));
    }

    public function bulkApprove(): void
    {
        $count = 0;

        foreach ($this->selectedRequests() as $request) {
            if (! auth()->user()->can('fulfill', $request)) {
                continue;
            }

            $this->documentRequests->markReady($request, auth()->user(), __('Approved in bulk.'));
            $count++;
        }

        $this->clearSelection();
        session()->flash('success', __('Approved :count selected request(s).', ['count' => $count]));
    }

    public function bulkReject(): void
    {
        $count = 0;

        foreach ($this->selectedRequests() as $request) {
            if (! auth()->user()->can('reject', $request)) {
                continue;
            }

            $this->documentRequests->reject($request, auth()->user(), __('Rejected in bulk.'));
            $count++;
        }

        $this->clearSelection();
        session()->flash('success', __('Rejected :count selected request(s).', ['count' => $count]));
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
            'adminDocumentTypes' => $this->documentRequests->activeDocumentTypeOptions(forAdmin: true),
            'adminDocumentTypeProfiles' => EmployeeDocumentType::query()
                ->with(['templates' => fn ($query) => $query->where('is_active', true)->latest()])
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->keyBy('code'),
            'selectedDocumentTypeProfile' => $this->selectedDocumentTypeProfile(),
            'statuses' => EmployeeDocumentRequest::statuses(),
            'employees' => User::query()
                ->where('group', 'user')
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name', 'nip', 'email']),
        ]);
    }

    private function resetReviewDialog(): void
    {
        $this->selectedId = null;
        $this->reviewNote = '';
        $this->confirmingReady = false;
        $this->confirmingRejection = false;
    }

    private function resetCreateForm(): void
    {
        $this->targetUserId = '';
        $this->targetUserIds = [];
        $this->documentType = array_key_first($this->documentRequests->activeDocumentTypeOptions(forAdmin: true))
            ?? EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE;
        $this->purpose = '';
        $this->details = '';
        $this->dueDate = '';
        $profile = $this->selectedDocumentTypeProfile();
        $this->generateImmediately = (bool) ($profile?->auto_generate_enabled && $profile?->activeTemplate());
    }

    private function selectedDocumentTypeProfile(): ?EmployeeDocumentType
    {
        return EmployeeDocumentType::query()
            ->with(['templates' => fn ($query) => $query->where('is_active', true)->latest()])
            ->where('code', $this->documentType)
            ->first();
    }

    private function clearSelection(): void
    {
        $this->selectedRequestIds = [];
        $this->selectAll = false;
    }

    private function currentPageRequestIds(): array
    {
        return $this->documentRequests
            ->managementQuery($this->statusFilter, $this->typeFilter, $this->search)
            ->limit(12)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function selectedRequests()
    {
        return EmployeeDocumentRequest::query()
            ->with(['user', 'documentType'])
            ->whereIn('id', $this->selectedRequestIds)
            ->get();
    }
}
