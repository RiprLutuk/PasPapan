<?php

namespace App\Support;

use App\Models\Schedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
        $requestDate = Carbon::parse($payload['schedule_date'])->toDateString();
        $scheduleId = $payload['schedule_id'] ?? null;

        $schedule = Schedule::query()
            ->with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', today())
            ->when(
                $scheduleId,
                fn ($query) => $query->whereKey($scheduleId),
                fn ($query) => $query->whereDate('date', $requestDate),
            )
            ->first();

        if ($schedule && (int) $schedule->shift_id === (int) $payload['requested_shift_id']) {
            return [
                'ok' => false,
                'field' => 'requestedShiftId',
                'message' => __('Choose a different shift from your current schedule.'),
            ];
        }

        $hasPending = ShiftSwapRequest::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->where(function ($query) use ($schedule, $requestDate): void {
                $query->whereDate('schedule_date', $requestDate);

                if ($schedule) {
                    $query->orWhere('schedule_id', $schedule->id);
                }
            })
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
            'schedule_id' => $schedule?->id,
            'schedule_date' => $schedule?->date?->toDateString() ?? $requestDate,
            'current_shift_id' => $schedule?->shift_id,
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

        $request->loadMissing('schedule');
        $scheduleDate = $request->effectiveScheduleDate()?->toDateString();

        if (! $scheduleDate) {
            return __('Schedule date is missing for this shift swap request.');
        }

        DB::transaction(function () use ($request, $actor): void {
            $scheduleDate = $request->effectiveScheduleDate()->toDateString();
            $schedule = $request->schedule ?? Schedule::query()->firstOrNew([
                'user_id' => $request->user_id,
                'date' => $scheduleDate,
            ]);

            $schedule->fill([
                'shift_id' => $request->requested_shift_id,
                'is_off' => false,
            ])->save();

            $request->update([
                'schedule_id' => $schedule->id,
                'schedule_date' => $scheduleDate,
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

    public function managementQuery(User $actor, string $statusFilter = 'pending', string $search = ''): Builder
    {
        return ShiftSwapRequest::query()
            ->with([
                'user.division',
                'user.jobTitle',
                'schedule.shift',
                'currentShift',
                'requestedShift',
                'replacementUser',
                'reviewer',
            ])
            ->whereHas('user', fn (Builder $query) => $query->managedBy($actor))
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('reason', 'like', '%'.$search.'%')
                        ->orWhere('rejection_note', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%')
                                ->orWhereHas('division', fn (Builder $divisionQuery) => $divisionQuery->where('name', 'like', '%'.$search.'%'))
                                ->orWhereHas('jobTitle', fn (Builder $jobTitleQuery) => $jobTitleQuery->where('name', 'like', '%'.$search.'%'));
                        })
                        ->orWhereHas('requestedShift', fn (Builder $shiftQuery) => $shiftQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByRaw("case when status = ? then 0 when status = ? then 1 else 2 end", [
                ShiftSwapRequest::STATUS_PENDING,
                ShiftSwapRequest::STATUS_APPROVED,
            ])
            ->latest('created_at');
    }

    private function canManagerReview(ShiftSwapRequest $request, User $actor): bool
    {
        if ($request->status !== ShiftSwapRequest::STATUS_PENDING) {
            return false;
        }

        if ($actor->can('manageShiftSwapApprovals')) {
            return User::query()
                ->managedBy($actor)
                ->whereKey($request->user_id)
                ->exists();
        }

        return $this->approvalActors->subordinateIds($actor)->contains($request->user_id);
    }
}
