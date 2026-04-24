<?php

namespace App\Support;

use App\Models\Schedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShiftSwapRequestService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, request?: ShiftSwapRequest, field?: string, message?: string}
     */
    public function submit(User $user, array $payload): array
    {
        $schedule = Schedule::query()
            ->with('shift')
            ->whereKey($payload['schedule_id'])
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', today())
            ->first();

        if (! $schedule) {
            return [
                'ok' => false,
                'field' => 'scheduleId',
                'message' => __('Choose one of your upcoming schedules.'),
            ];
        }

        if ((int) $schedule->shift_id === (int) $payload['requested_shift_id']) {
            return [
                'ok' => false,
                'field' => 'requestedShiftId',
                'message' => __('Choose a different shift from your current schedule.'),
            ];
        }

        $hasPending = ShiftSwapRequest::query()
            ->where('schedule_id', $schedule->id)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return [
                'ok' => false,
                'field' => 'scheduleId',
                'message' => __('This schedule already has a pending shift swap request.'),
            ];
        }

        $request = ShiftSwapRequest::create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'current_shift_id' => $schedule->shift_id,
            'requested_shift_id' => $payload['requested_shift_id'],
            'replacement_user_id' => $payload['replacement_user_id'] ?: null,
            'reason' => $payload['reason'],
            'status' => ShiftSwapRequest::STATUS_PENDING,
        ]);

        return [
            'ok' => true,
            'request' => $request,
        ];
    }

    public function approve(ShiftSwapRequest $request, User $actor): string
    {
        if (! $this->canManagerReview($request, $actor)) {
            return __('You are not allowed to review this shift swap request.');
        }

        DB::transaction(function () use ($request, $actor): void {
            $request->loadMissing('schedule');

            $request->schedule->update([
                'shift_id' => $request->requested_shift_id,
                'is_off' => false,
            ]);

            $request->update([
                'status' => ShiftSwapRequest::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_note' => null,
            ]);
        });

        return __('Shift swap request approved and schedule updated.');
    }

    public function reject(ShiftSwapRequest $request, User $actor, ?string $note = null): string
    {
        if (! $this->canManagerReview($request, $actor)) {
            return __('You are not allowed to review this shift swap request.');
        }

        $request->update([
            'status' => ShiftSwapRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_note' => $note,
        ]);

        return __('Shift swap request rejected.');
    }

    public function paginateForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return ShiftSwapRequest::query()
            ->with(['schedule.shift', 'currentShift', 'requestedShift', 'replacementUser', 'reviewer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    private function canManagerReview(ShiftSwapRequest $request, User $actor): bool
    {
        return $request->status === ShiftSwapRequest::STATUS_PENDING
            && $this->approvalActors->subordinateIds($actor)->contains($request->user_id);
    }
}
