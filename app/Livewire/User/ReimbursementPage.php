<?php

namespace App\Livewire\User;

use App\Models\Reimbursement;
use App\Support\UserReimbursementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReimbursementPage extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    protected UserReimbursementService $reimbursementService;

    public $claims;
    public $limit = 5;
    public $isCreating = false;
    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';

    // Form Fields
    public $date;
    public $type = 'medical';
    public $amount;
    public $description;
    public $attachment;

    protected $rules = [
        'date' => 'required|date',
        'type' => 'required|string|in:medical,transport,optical,dental,project,other',
        'amount' => 'required|numeric|min:1',
        'description' => 'required|string|max:500',
        'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
    ];

    public function boot(UserReimbursementService $reimbursementService): void
    {
        $this->reimbursementService = $reimbursementService;
    }

    public function mount()
    {
        $this->authorize('viewAny', Reimbursement::class);

        $this->date = now()->format('Y-m-d');
    }

    public function create()
    {
        $this->reset(['amount', 'description', 'attachment']);
        $this->date = now()->format('Y-m-d');
        $this->type = 'medical';
        $this->isCreating = true;
    }

    public function cancel()
    {
        $this->isCreating = false;
        $this->reset(['amount', 'description', 'attachment']);
    }

    public function updatingSearch()
    {
        $this->limit = 5;
    }

    public function updatingStatusFilter()
    {
        $this->limit = 5;
    }

    public function updatingTypeFilter()
    {
        $this->limit = 5;
    }

    public function save()
    {
        $this->authorize('create', Reimbursement::class);

        $this->validate();
        $this->reimbursementService->createClaim(Auth::user(), [
            'date' => $this->date,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
        ], $this->attachment);

        $this->isCreating = false;
        $this->reset(['amount', 'description', 'attachment']);
        $this->dispatch('refresh-notifications');
        $this->dispatch('success', 'Reimbursement claim submitted successfully.');
    }

    public function loadMore()
    {
        $this->limit += 10;
    }

    public function render()
    {
        $listing = $this->reimbursementService->claimListing(
            Auth::id(),
            $this->search,
            $this->statusFilter,
            $this->typeFilter,
            $this->limit,
        );
        $this->claims = $listing['claims'];

        return view('livewire.user.reimbursement-page', [
            'totalClaims' => $listing['total'],
        ])->layout('layouts.app');
    }
}
