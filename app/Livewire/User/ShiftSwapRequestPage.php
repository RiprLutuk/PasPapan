<?php

namespace App\Livewire\User;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Support\ShiftSwapRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftSwapRequestPage extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $scheduleId = null;

    public ?int $requestedShiftId = null;

    public ?string $replacementUserId = null;

    public string $reason = '';

    #[Url(as: 'schedule')]
    public ?int $prefillScheduleId = null;

    protected ShiftSwapRequestService $shiftSwapRequests;

    public function boot(ShiftSwapRequestService $shiftSwapRequests): void
    {
        $this->shiftSwapRequests = $shiftSwapRequests;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ShiftSwapRequest::class);

        if ($this->prefillScheduleId) {
            $this->create($this->prefillScheduleId);
        }
    }

    public function create(?int $scheduleId = null): void
    {
        $this->authorize('create', ShiftSwapRequest::class);
        $this->reset(['scheduleId', 'requestedShiftId', 'replacementUserId', 'reason']);
        $this->scheduleId = $scheduleId;
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->prefillScheduleId = null;
    }

    public function store(): void
    {
        $this->authorize('create', ShiftSwapRequest::class);
        $this->replacementUserId = $this->replacementUserId ?: null;

        $validated = $this->validate([
            'scheduleId' => ['required', 'integer', 'exists:schedules,id'],
            'requestedShiftId' => ['required', 'integer', 'exists:shifts,id'],
            'replacementUserId' => ['nullable', 'string', Rule::exists('users', 'id')->where(fn ($query) => $query->where('group', 'user'))],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if ($validated['replacementUserId'] === Auth::id()) {
            $this->addError('replacementUserId', __('Choose another employee as replacement.'));

            return;
        }

        $result = $this->shiftSwapRequests->submit(Auth::user(), [
            'schedule_id' => $validated['scheduleId'],
            'requested_shift_id' => $validated['requestedShiftId'],
            'replacement_user_id' => $validated['replacementUserId'] ?? null,
            'reason' => $validated['reason'],
        ]);

        if (! $result['ok']) {
            $this->addError((string) $result['field'], (string) $result['message']);

            return;
        }

        $this->showModal = false;
        $this->prefillScheduleId = null;
        $this->reset(['scheduleId', 'requestedShiftId', 'replacementUserId', 'reason']);
        session()->flash('success', __('Shift swap request submitted successfully.'));
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.user.shift-swap-request-page', [
            'requests' => $this->shiftSwapRequests->paginateForUser($user),
            'schedules' => Schedule::query()
                ->with('shift')
                ->where('user_id', $user->id)
                ->whereDate('date', '>=', today())
                ->orderBy('date')
                ->limit(60)
                ->get(),
            'shifts' => Shift::query()->orderBy('name')->get(),
            'replacementUsers' => User::query()
                ->where('group', 'user')
                ->whereKeyNot($user->id)
                ->when($user->division_id, fn ($query) => $query->where('division_id', $user->division_id))
                ->orderBy('name')
                ->get(['id', 'name', 'division_id', 'job_title_id']),
        ])->layout('layouts.app');
    }
}
