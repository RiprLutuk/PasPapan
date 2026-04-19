<?php

namespace App\Livewire\User;

use Livewire\WithFileUploads;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Reimbursement;

class ReimbursementPage extends Component
{
    use WithFileUploads;

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

    public function mount()
    {
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
        // Sanitize Amount (Remove dots/commas from masking)
        // Example: "1.250.000" -> "1250000"
        if ($this->amount) {
            $this->amount = str_replace(['.', ','], '', (string) $this->amount);
        }

        $this->validate();

        $path = null;
        if ($this->attachment) {
            $path = $this->attachment->store('reimbursements', 'local');
        }

        Reimbursement::create([
            'user_id' => Auth::id(),
            'date' => $this->date,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'attachment' => $path,
            'status' => 'pending',
        ]);

        // Notify Supervisor AND Admins (Broad Visibility)
        $newReimbursement = Reimbursement::where('user_id', Auth::id())->latest()->first();
        if ($newReimbursement) {
            $supervisor = Auth::user()->supervisor;
            $admins = \App\Models\User::whereIn('group', ['admin', 'superadmin'])->get();
            
            $notifiable = $admins;
            if ($supervisor) {
                $notifiable = $notifiable->push($supervisor)->unique('id');
            }

            if ($notifiable->count() > 0) {
                 // Bell (Sync)
                 \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\ReimbursementRequested($newReimbursement));
                 
                 // Email (Queued)
                 \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\ReimbursementRequestedEmail($newReimbursement));
                 
                 // Force UI Refresh
                 $this->dispatch('refresh-notifications');
            }

            // Global Admin Email (Queued)
            $adminEmail = \App\Models\Setting::getValue('notif.admin_email');
            if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                 try {
                     \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                         ->notify(new \App\Notifications\ReimbursementRequestedEmail($newReimbursement));
                 } catch (\Throwable $e) {
                     // Log ignored
                 }
            }
        }

        $this->isCreating = false;
        $this->dispatch('success', 'Reimbursement claim submitted successfully.');
    }

    public function loadMore()
    {
        $this->limit += 10;
    }

    public function render()
    {
        $query = Reimbursement::where('user_id', Auth::id())
            ->when($this->search !== '', function ($builder) {
                $builder->where(function ($subQuery) {
                    $subQuery->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('type', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', fn ($builder) => $builder->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($builder) => $builder->where('type', $this->typeFilter))
            ->latest('date');

        $totalClaims = $query->count();
        $this->claims = $query->take($this->limit)->get();

        return view('livewire.user.reimbursement-page', [
            'totalClaims' => $totalClaims
        ])->layout('layouts.app');
    }
}
