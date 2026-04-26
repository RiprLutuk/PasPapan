<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Support\AttendanceCorrectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public bool $includeRequestedTimeIn = false;

    public bool $includeRequestedTimeOut = false;

    public bool $includeRequestedShift = false;

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
        $this->includeRequestedTimeIn = false;
        $this->includeRequestedTimeOut = false;
        $this->includeRequestedShift = false;
        $this->requestedTimeIn = null;
        $this->requestedTimeOut = null;
        $this->requestedShiftId = null;
        $this->reason = '';
    }

    public function closeModal(): void
    {
        $this->resetErrorBag();
        $this->showCreateModal = false;
    }

    public function updatedIncludeRequestedTimeIn(bool $value): void
    {
        if (! $value) {
            $this->requestedTimeIn = null;

            return;
        }

        $this->requestedTimeIn ??= $this->defaultRequestedDateTime('in');
    }

    public function updatedIncludeRequestedTimeOut(bool $value): void
    {
        if (! $value) {
            $this->requestedTimeOut = null;

            return;
        }

        $this->requestedTimeOut ??= $this->defaultRequestedDateTime('out');
    }

    public function updatedIncludeRequestedShift(bool $value): void
    {
        if (! $value) {
            $this->requestedShiftId = null;
        }
    }

    public function updatedAttendanceDate(): void
    {
        if ($this->includeRequestedTimeIn) {
            $this->requestedTimeIn = $this->defaultRequestedDateTime('in');
        }

        if ($this->includeRequestedTimeOut) {
            $this->requestedTimeOut = $this->defaultRequestedDateTime('out');
        }
    }

    public function updatedRequestedShiftId(): void
    {
        if ($this->includeRequestedTimeIn) {
            $this->requestedTimeIn = $this->defaultRequestedDateTime('in');
        }

        if ($this->includeRequestedTimeOut) {
            $this->requestedTimeOut = $this->defaultRequestedDateTime('out');
        }
    }

    public function save(): void
    {
        $this->authorize('create', AttendanceCorrection::class);

        $validated = $this->validate([
            'attendanceDate' => ['required', 'date'],
            'includeRequestedTimeIn' => ['boolean'],
            'includeRequestedTimeOut' => ['boolean'],
            'includeRequestedShift' => ['boolean'],
            'requestedTimeIn' => ['nullable', 'date_format:Y-m-d H:i'],
            'requestedTimeOut' => ['nullable', 'date_format:Y-m-d H:i'],
            'requestedShiftId' => ['nullable', 'exists:shifts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$requestedTimeIn, $requestedTimeOut, $requestType] = $this->validateRequestPayload();

        $this->correctionService->submit(auth()->user(), [
            'attendance_date' => $validated['attendanceDate'],
            'request_type' => $requestType,
            'requested_time_in' => $requestedTimeIn,
            'requested_time_out' => $requestedTimeOut,
            'requested_shift_id' => $this->includeRequestedShift ? $validated['requestedShiftId'] : null,
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
            'snapshotTimeIn' => $this->attendanceSnapshotDateTime($existingAttendance, 'in'),
            'snapshotTimeOut' => $this->attendanceSnapshotDateTime($existingAttendance, 'out'),
            'shifts' => Shift::query()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }

    private function validateRequestPayload(): array
    {
        $attendance = Attendance::query()
            ->where('user_id', auth()->id())
            ->whereDate('date', $this->attendanceDate)
            ->first();

        $messages = [];

        if (! $this->includeRequestedTimeIn && ! $this->includeRequestedTimeOut && ! $this->includeRequestedShift) {
            $messages['includeRequestedTimeIn'] = __('Select at least one correction to request.');
        }

        if ($this->includeRequestedTimeIn && ! $this->requestedTimeIn) {
            $messages['requestedTimeIn'] = __('Requested check in time is required.');
        }

        if ($this->includeRequestedTimeOut && ! $this->requestedTimeOut) {
            $messages['requestedTimeOut'] = __('Requested check out time is required.');
        }

        if ($this->includeRequestedShift && ! $this->requestedShiftId) {
            $messages['requestedShiftId'] = __('Please choose the corrected shift.');
        }

        $requestedTimeIn = $this->includeRequestedTimeIn
            ? $this->buildRequestedDateTime($this->requestedTimeIn)
            : null;

        $requestedTimeOut = $this->includeRequestedTimeOut
            ? $this->buildRequestedDateTime($this->requestedTimeOut)
            : null;

        if ($this->includeRequestedTimeOut && ! $this->includeRequestedTimeIn && ! $attendance?->time_in) {
            $messages['attendanceDate'] = __('A check out correction requires an existing or requested check in record.');
        }

        if ($this->includeRequestedShift && ! $attendance && ! $this->includeRequestedTimeIn && ! $this->includeRequestedTimeOut) {
            $messages['attendanceDate'] = __('A shift correction requires an existing attendance record.');
        }

        $referenceTimeIn = $requestedTimeIn ?? $this->attendanceSnapshotDateTime($attendance, 'in');

        if ($requestedTimeOut && $referenceTimeIn && $requestedTimeOut->lte($referenceTimeIn)) {
            $messages['requestedTimeOut'] = __('Requested check out time must be later than check in time.');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return [
            $requestedTimeIn?->format('Y-m-d H:i:s'),
            $requestedTimeOut?->format('Y-m-d H:i:s'),
            $this->inferRequestType($attendance, $requestedTimeIn, $requestedTimeOut),
        ];
    }

    private function inferRequestType(?Attendance $attendance, ?\Illuminate\Support\Carbon $requestedTimeIn, ?\Illuminate\Support\Carbon $requestedTimeOut): string
    {
        if ($this->includeRequestedShift && ! $requestedTimeIn && ! $requestedTimeOut) {
            return AttendanceCorrection::TYPE_WRONG_SHIFT;
        }

        if ($requestedTimeIn && $requestedTimeOut) {
            return AttendanceCorrection::TYPE_WRONG_TIME;
        }

        if ($requestedTimeIn) {
            return $attendance?->time_in
                ? AttendanceCorrection::TYPE_WRONG_TIME
                : AttendanceCorrection::TYPE_MISSING_CHECK_IN;
        }

        if ($requestedTimeOut) {
            return $attendance?->time_out
                ? AttendanceCorrection::TYPE_WRONG_TIME
                : AttendanceCorrection::TYPE_MISSING_CHECK_OUT;
        }

        return AttendanceCorrection::TYPE_WRONG_SHIFT;
    }

    private function buildRequestedDateTime(?string $dateTime): ?\Illuminate\Support\Carbon
    {
        if (! $dateTime) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($dateTime);
    }

    private function currentAttendance(): ?Attendance
    {
        return Attendance::query()
            ->with('shift')
            ->where('user_id', auth()->id())
            ->whereDate('date', $this->attendanceDate)
            ->first();
    }

    private function defaultRequestedDateTime(string $direction): string
    {
        $attendance = $this->currentAttendance();
        $date = \Illuminate\Support\Carbon::parse($this->attendanceDate);

        $snapshotValue = $this->attendanceSnapshotDateTime($attendance, $direction);

        if ($snapshotValue) {
            return $snapshotValue->format('Y-m-d H:i');
        }

        $shift = $attendance?->shift
            ?? ($this->requestedShiftId ? Shift::query()->find($this->requestedShiftId) : null);

        if ($direction === 'in') {
            return $date->copy()
                ->setTimeFromTimeString($shift?->start_time ?: '08:00:00')
                ->format('Y-m-d H:i');
        }

        $defaultOut = $date->copy()->setTimeFromTimeString($shift?->end_time ?: '17:00:00');

        if ($shift?->is_overnight) {
            $defaultOut->addDay();
        }

        return $defaultOut->format('Y-m-d H:i');
    }

    private function attendanceSnapshotDateTime(?Attendance $attendance, string $direction): ?\Illuminate\Support\Carbon
    {
        if (! $attendance) {
            return null;
        }

        $rawValue = $direction === 'in' ? $attendance->time_in : $attendance->time_out;

        if (! $rawValue) {
            return null;
        }

        $baseDate = \Illuminate\Support\Carbon::parse($attendance->date ?? $this->attendanceDate);
        $rawDateTime = \Illuminate\Support\Carbon::parse($rawValue);
        $normalized = $baseDate->copy()->setTime(
            (int) $rawDateTime->format('H'),
            (int) $rawDateTime->format('i'),
            (int) $rawDateTime->format('s')
        );

        if ($direction !== 'out') {
            return $normalized;
        }

        $normalizedTimeIn = $this->attendanceSnapshotDateTime($attendance, 'in');

        if ($attendance->shift?->is_overnight || ($normalizedTimeIn && $normalized->lessThanOrEqualTo($normalizedTimeIn))) {
            $normalized->addDay();
        }

        return $normalized;
    }
}
