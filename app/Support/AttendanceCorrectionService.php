<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\AttendanceCorrectionStatusUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    public function submit(User $user, array $payload): AttendanceCorrection
    {
        $attendanceDate = Carbon::parse($payload['attendance_date'])->toDateString();
        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $attendanceDate)
            ->first();

        return AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance?->id,
            'attendance_date' => $attendanceDate,
            'request_type' => $payload['request_type'],
            'requested_time_in' => $this->nullableDateTime($payload['requested_time_in'] ?? null),
            'requested_time_out' => $this->nullableDateTime($payload['requested_time_out'] ?? null),
            'requested_shift_id' => $payload['requested_shift_id'] ?: null,
            'current_snapshot' => $this->snapshot($attendance),
            'reason' => trim((string) $payload['reason']),
            'status' => $this->needsSupervisorReview($user)
                ? AttendanceCorrection::STATUS_PENDING
                : AttendanceCorrection::STATUS_PENDING_ADMIN,
        ]);
    }

    public function managementQuery(User $actor, string $statusFilter = 'pending', string $typeFilter = 'all', string $search = ''): Builder
    {
        return AttendanceCorrection::query()
            ->with(['user.jobTitle', 'attendance.shift', 'requestedShift', 'headApprover', 'reviewer'])
            ->when(! $actor->can('accessAdminPanel'), function (Builder $query) use ($actor) {
                $query->whereIn('user_id', $this->approvalActors->subordinateIds($actor))
                    ->where('status', AttendanceCorrection::STATUS_PENDING);
            })
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn (Builder $query) => $query->where('request_type', $typeFilter))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('reason', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending_admin' THEN 0 WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at');
    }

    public function approve(AttendanceCorrection $correction, User $actor): string
    {
        if (! $actor->can('accessAdminPanel')) {
            if (! $this->canSupervisorReview($correction, $actor) || $correction->status !== AttendanceCorrection::STATUS_PENDING) {
                throw new AuthorizationException;
            }

            $correction->update([
                'status' => AttendanceCorrection::STATUS_PENDING_ADMIN,
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
                'rejection_note' => null,
            ]);

            $this->notifyStatusUpdated($correction);

            return __('Attendance correction forwarded to admin for final review.');
        }

        DB::transaction(function () use ($correction, $actor) {
            $correction->loadMissing(['user', 'attendance.shift', 'requestedShift']);

            $attendance = $correction->attendance ?? Attendance::query()->firstOrNew([
                'user_id' => $correction->user_id,
                'date' => $correction->attendance_date->toDateString(),
            ]);

            $attendance->user_id = $correction->user_id;
            $attendance->date = $correction->attendance_date->toDateString();

            if ($correction->requested_shift_id) {
                $attendance->shift_id = $correction->requested_shift_id;
            }

            if ($correction->requested_time_in) {
                $attendance->time_in = $correction->requested_time_in;
            }

            if ($correction->requested_time_out) {
                $attendance->time_out = $correction->requested_time_out;
            }

            $attendance->status = $this->resolvedStatus(
                $attendance->time_in ? Carbon::parse($attendance->time_in) : null,
                $correction->requestedShift ?? $attendance->shift,
                (int) \App\Models\Setting::getValue('attendance.grace_period', 10),
                $attendance->status,
            );

            $attendance->save();

            $correction->update([
                'attendance_id' => $attendance->id,
                'status' => AttendanceCorrection::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_note' => null,
            ]);

            Attendance::clearUserAttendanceCache($correction->user, Carbon::parse($correction->attendance_date));
        });

        $this->notifyStatusUpdated($correction);

        return __('Attendance correction approved and applied.');
    }

    public function reject(AttendanceCorrection $correction, User $actor, ?string $note = null): string
    {
        if (! $actor->can('accessAdminPanel') && ! $this->canSupervisorReview($correction, $actor)) {
            throw new AuthorizationException;
        }

        $correction->update([
            'status' => AttendanceCorrection::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_note' => $note,
        ]);

        $this->notifyStatusUpdated($correction);

        return __('Attendance correction rejected.');
    }

    private function snapshot(?Attendance $attendance): ?array
    {
        if (! $attendance) {
            return null;
        }

        return [
            'status' => $attendance->status,
            'shift_id' => $attendance->shift_id,
            'shift_name' => $attendance->shift?->name,
            'time_in' => $attendance->time_in?->format('Y-m-d H:i:s'),
            'time_out' => $attendance->time_out?->format('Y-m-d H:i:s'),
        ];
    }

    private function nullableDateTime(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    private function resolvedStatus(?Carbon $timeIn, ?Shift $shift, int $gracePeriod, ?string $fallback): string
    {
        if (! $timeIn) {
            return $fallback ?: 'present';
        }

        if (! $shift) {
            return 'present';
        }

        $shiftStart = Carbon::parse($shift->start_time)->setDate($timeIn->year, $timeIn->month, $timeIn->day);

        return $timeIn->gt($shiftStart->copy()->addMinutes($gracePeriod)) ? 'late' : 'present';
    }

    private function needsSupervisorReview(User $user): bool
    {
        return (bool) optional($user->supervisor)->id;
    }

    private function canSupervisorReview(AttendanceCorrection $correction, User $actor): bool
    {
        return $this->approvalActors->subordinateIds($actor)->contains($correction->user_id);
    }

    private function notifyStatusUpdated(AttendanceCorrection $correction): void
    {
        $correction->refresh()->loadMissing('user');
        $correction->user?->notify(new AttendanceCorrectionStatusUpdated($correction));
    }
}
