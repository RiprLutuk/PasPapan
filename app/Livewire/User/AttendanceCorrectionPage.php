<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Support\AttendanceCorrectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceCorrectionPage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected AttendanceCorrectionService $correctionService;

    public bool $showCreateModal = false;

    public string $statusFilter = 'all';

    public string $search = '';

    public string $attendanceDate = '';

    public string $requestType = AttendanceCorrection::TYPE_MISSING_CHECK_IN;

    public ?string $requestedTimeIn = null;

    public ?string $requestedTimeOut = null;

    public $requestedShiftId = null;

    public string $reason = '';

    public function boot(AttendanceCorrectionService $correctionService): void
    {
        $this->correctionService = $correctionService;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', AttendanceCorrection::class);
        $this->attendanceDate = now()->toDateString();
    }

    public function create(): void
    {
        $this->authorize('create', AttendanceCorrection::class);

        $this->resetErrorBag();
        $this->showCreateModal = true;
        $this->attendanceDate = now()->toDateString();
        $this->requestType = AttendanceCorrection::TYPE_MISSING_CHECK_IN;
        $this->requestedTimeIn = null;
        $this->requestedTimeOut = null;
        $this->requestedShiftId = null;
        $this->reason = '';
    }

    public function closeModal(): void
    {
        $this->showCreateModal = false;
    }

    public function save(): void
    {
        $this->authorize('create', AttendanceCorrection::class);

        $validated = $this->validate([
            'attendanceDate' => ['required', 'date'],
            'requestType' => ['required', Rule::in(array_keys(AttendanceCorrection::requestTypes()))],
            'requestedTimeIn' => ['nullable', 'date'],
            'requestedTimeOut' => ['nullable', 'date'],
            'requestedShiftId' => ['nullable', 'exists:shifts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->validateRequestPayload();

        $this->correctionService->submit(auth()->user(), [
            'attendance_date' => $validated['attendanceDate'],
            'request_type' => $validated['requestType'],
            'requested_time_in' => $validated['requestedTimeIn'],
            'requested_time_out' => $validated['requestedTimeOut'],
            'requested_shift_id' => $validated['requestedShiftId'],
            'reason' => $validated['reason'],
        ]);

        $this->closeModal();
        $this->dispatch('refresh-notifications');
        session()->flash('success', __('Attendance correction request submitted successfully.'));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', AttendanceCorrection::class);

        $corrections = AttendanceCorrection::query()
            ->with(['attendance.shift', 'requestedShift', 'headApprover'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested->where('reason', 'like', '%'.$this->search.'%')
                        ->orWhere('request_type', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate(10);

        $existingAttendance = Attendance::query()
            ->with('shift')
            ->where('user_id', auth()->id())
            ->whereDate('date', $this->attendanceDate)
            ->first();

        return view('livewire.user.attendance-correction-page', [
            'corrections' => $corrections,
            'existingAttendance' => $existingAttendance,
            'requestTypes' => AttendanceCorrection::requestTypes(),
            'shifts' => Shift::query()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }

    private function validateRequestPayload(): void
    {
        $attendance = Attendance::query()
            ->where('user_id', auth()->id())
            ->whereDate('date', $this->attendanceDate)
            ->first();

        $messages = [];

        if ($this->requestType === AttendanceCorrection::TYPE_MISSING_CHECK_IN && ! $this->requestedTimeIn) {
            $messages['requestedTimeIn'] = __('Requested check in time is required.');
        }

        if ($this->requestType === AttendanceCorrection::TYPE_MISSING_CHECK_OUT && ! $this->requestedTimeOut) {
            $messages['requestedTimeOut'] = __('Requested check out time is required.');
        }

        if ($this->requestType === AttendanceCorrection::TYPE_WRONG_TIME && ! $this->requestedTimeIn && ! $this->requestedTimeOut) {
            $messages['requestedTimeIn'] = __('Please fill at least one corrected time.');
        }

        if ($this->requestType === AttendanceCorrection::TYPE_WRONG_SHIFT && ! $this->requestedShiftId) {
            $messages['requestedShiftId'] = __('Please choose the corrected shift.');
        }

        if ($this->requestType === AttendanceCorrection::TYPE_MISSING_CHECK_OUT && ! $attendance?->time_in) {
            $messages['attendanceDate'] = __('A missing check out request requires an existing check in record.');
        }

        if ($this->requestType === AttendanceCorrection::TYPE_WRONG_SHIFT && ! $attendance) {
            $messages['attendanceDate'] = __('A shift correction requires an existing attendance record.');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }
}
