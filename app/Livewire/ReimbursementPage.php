<?php

namespace App\Livewire;

use Livewire\WithFileUploads;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Reimbursement;

class ReimbursementPage extends Component
{
    use WithFileUploads;

    public $claims;
    public $isCreating = false;

    // Form Fields
    public $date;
    public $type = 'medical';
    public $amount;
    public $description;
    public $attachment;

    protected $rules = [
        'date' => 'required|date',
        'type' => 'required|string|in:medical,transport,optical,dental,other',
        'amount' => 'required|numeric|min:1',
        'description' => 'required|string|max:500',
        'attachment' => 'nullable|file|max:10240', // 10MB max
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

    public function save()
    {
        $this->validate();

        $path = null;
        if ($this->attachment) {
            $path = $this->attachment->store('reimbursements', 'public');
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

        $this->isCreating = false;
        $this->dispatch('success', 'Reimbursement claim submitted successfully.');
    }

    public function render()
    {
        $this->claims = Reimbursement::where('user_id', Auth::id())
            ->latest('date')
            ->get();

        return view('livewire.reimbursement-page')->layout('layouts.app');
    }
}
