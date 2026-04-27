<?php

namespace App\Livewire\User;

use App\Models\Overtime;
use App\Support\UserOvertimeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OvertimeRequest extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $date;

    public $start_time;

    public $end_time;

    public $reason;

    public $showModal = false;

    protected UserOvertimeService $overtimeService;

    protected $rules = [
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i', // Removed after:start_time to allow crossing midnight
        'reason' => 'required|string|min:5',
    ];

    public function boot(UserOvertimeService $overtimeService): void
    {
        $this->overtimeService = $overtimeService;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Overtime::class);
    }

    public function render()
    {
        $overtimes = $this->overtimeService->paginateForUser(Auth::id());

        return view('livewire.user.overtime-request', [
            'overtimes' => $overtimes,
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->authorize('create', Overtime::class);

        $this->reset(['date', 'start_time', 'end_time', 'reason']);
        $this->showModal = true;
    }

    public function store()
    {
        $this->authorize('create', Overtime::class);

        $this->validate();

        $result = $this->overtimeService->submit(Auth::user(), [
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'reason' => $this->reason,
        ]);

        if (! $result['ok']) {
            $this->addError((string) $result['field'], (string) $result['message']);

            return;
        }

        $this->showModal = false;
        $this->reset(['date', 'start_time', 'end_time', 'reason']);
        $this->dispatch('refresh-notifications');
        session()->flash('success', __('Overtime request submitted successfully.'));
    }

    public function close()
    {
        $this->showModal = false;
    }
}
