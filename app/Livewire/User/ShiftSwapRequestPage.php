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

    public ?string $scheduleDate = null;

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
        $this->resetErrorBag();
        $this->reset(['scheduleId', 'scheduleDate', 'requestedShiftId', 'replacementUserId', 'reason']);
        $this->scheduleId = $scheduleId;
        $this->scheduleDate = $this->dateFromScheduleId($scheduleId);
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->resetErrorBag();
        $this->showModal = false;
        $this->prefillScheduleId = null;
    }

    public function updatedScheduleDate(?string $value): void
    {
        $this->scheduleId = $this->scheduleIdFromDate($value);
        $this->resetErrorBag('scheduleDate');
        $this->resetErrorBag('scheduleId');
    }

    public function store(): void
    {
        $this->authorize('create', ShiftSwapRequest::class);
        $this->replacementUserId = $this->replacementUserId ?: null;

        if (! $this->scheduleDate && $this->scheduleId) {
            $this->scheduleDate = $this->dateFromScheduleId($this->scheduleId);
        } else {
            $this->scheduleId = $this->scheduleIdFromDate($this->scheduleDate);
        }

        $validated = $this->validate(
            [
                'scheduleDate' => ['required', 'date', 'after_or_equal:today'],
                'scheduleId' => ['nullable', 'integer', 'exists:schedules,id'],
                'requestedShiftId' => ['required', 'integer', 'exists:shifts,id'],
                'replacementUserId' => ['nullable', 'string', Rule::exists('users', 'id')->where(fn ($query) => $query->where('group', 'user'))],
                'reason' => ['required', 'string', 'min:5', 'max:1000'],
            ],
            [],
            [
                'scheduleDate' => __('Schedule Date'),
            ],
        );

        if ($validated['replacementUserId'] === Auth::id()) {
            $this->addError('replacementUserId', __('Choose another employee as replacement.'));

            return;
        }

        $result = $this->shiftSwapRequests->submit(Auth::user(), [
            'schedule_id' => $validated['scheduleId'] ?? null,
            'schedule_date' => $validated['scheduleDate'],
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
        $this->reset(['scheduleId', 'scheduleDate', 'requestedShiftId', 'replacementUserId', 'reason']);
        session()->flash('success', __('Shift swap request submitted successfully.'));
    }

    private function scheduleIdFromDate(?string $date): ?int
    {
        if (! $date) {
            return null;
        }

        return Schedule::query()
            ->where('user_id', Auth::id())
            ->whereDate('date', $date)
            ->value('id');
    }

    private function dateFromScheduleId(?int $scheduleId): ?string
    {
        if (! $scheduleId) {
            return null;
        }

        $schedule = Schedule::query()
            ->where('user_id', Auth::id())
            ->whereKey($scheduleId)
            ->first(['date']);

        return $schedule?->date?->toDateString();
    }

    public function render()
    {
        $user = Auth::user();
        $replacementUsers = User::query()
            ->with('jobTitle')
            ->where('group', 'user')
            ->whereKeyNot($user->id)
            ->orderByRaw('division_id = ? desc', [$user->division_id ?? 0])
            ->orderBy('name')
            ->get(['id', 'name', 'division_id', 'job_title_id']);

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
            'replacementUsers' => $replacementUsers,
            'replacementUserOptions' => $replacementUsers
                ->map(fn (User $replacement): array => [
                    'id' => (string) $replacement->id,
                    'name' => trim($replacement->name . ($replacement->jobTitle?->name ? ' - ' . $replacement->jobTitle->name : '')),
                ])
                ->values()
                ->all(),
        ])->layout('layouts.app');
    }
}
