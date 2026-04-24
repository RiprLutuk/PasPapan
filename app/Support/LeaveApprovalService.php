<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Support\Collection;

class LeaveApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    /**
     * @return Collection<string, \Illuminate\Support\Collection<int, Attendance>>
     */
    public function groupedRequests(
        User $actor,
        string $statusFilter = 'all',
        string $requestTypeFilter = 'all',
        string $search = '',
    ): Collection {
        $query = Attendance::query()
            ->with(['user.division', 'user.jobTitle'])
            ->whereIn('status', Attendance::REQUEST_STATUSES);

        if ($statusFilter !== 'all') {
            $query->where('approval_status', $statusFilter);
        }

        if (! $actor->can('accessAdminPanel')) {
            $query->whereIn('user_id', $this->approvalActors->subordinateIds($actor));
        }

        if ($requestTypeFilter !== 'all') {
            $query->where('status', $requestTypeFilter);
        }

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('note', 'like', '%'.$search.'%')
                    ->orWhere('rejection_note', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('nip', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy(function (Attendance $attendance) {
                return $attendance->user_id.'|'.$attendance->status.'|'.$attendance->approval_status.'|'.trim((string) $attendance->note);
            });
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function approve(array $ids, User $actor): void
    {
        $authorizedIds = $this->authorizedRequestIds($ids, $actor);

        if (count($authorizedIds) !== count($ids)) {
            abort(403, 'Unauthorized action.');
        }

        Attendance::query()
            ->whereIn('id', $authorizedIds)
            ->update([
                'approval_status' => Attendance::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

        $this->notifyUpdated($authorizedIds);
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function reject(array $ids, User $actor, ?string $rejectionNote = null): void
    {
        $authorizedIds = $this->authorizedRequestIds($ids, $actor);

        if (count($authorizedIds) !== count($ids)) {
            abort(403, 'Unauthorized action.');
        }

        Attendance::query()
            ->whereIn('id', $authorizedIds)
            ->update([
                'approval_status' => Attendance::STATUS_REJECTED,
                'rejection_note' => $rejectionNote,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

        $this->notifyUpdated($authorizedIds);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    public function authorizedRequestIds(array $ids, User $actor): array
    {
        $query = Attendance::query()
            ->whereIn('id', $ids)
            ->whereIn('status', Attendance::REQUEST_STATUSES);

        if ($actor->can('accessAdminPanel')) {
            return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $query
            ->whereIn('user_id', $this->approvalActors->subordinateIds($actor))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     */
    protected function notifyUpdated(array $ids): void
    {
        $attendances = Attendance::query()
            ->with('user')
            ->whereIn('id', $ids)
            ->get();

        foreach ($attendances as $attendance) {
            $attendance->user?->notify(new LeaveStatusUpdated($attendance));
        }
    }
}
