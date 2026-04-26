<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LeaveApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \Illuminate\Support\Collection<int, Attendance>>
     */
    public function groupedRequests(
        User $actor,
        string $statusFilter = 'all',
        string $requestTypeFilter = 'all',
        string $search = '',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $groups = $this->baseQuery($actor, $statusFilter, $requestTypeFilter, $search)
            ->selectRaw('user_id, status, leave_type_id, approval_status, note, MIN(date) as start_date, MAX(date) as end_date, COUNT(*) as day_count')
            ->groupBy('user_id', 'status', 'leave_type_id', 'approval_status', 'note')
            ->orderByDesc('end_date')
            ->paginate($perPage);

        $groups->setCollection($groups->getCollection()->map(function ($group) use ($actor, $statusFilter, $requestTypeFilter, $search) {
            return $this->baseQuery($actor, $statusFilter, $requestTypeFilter, $search)
                ->with(['user.division', 'user.jobTitle', 'leaveType'])
                ->where('user_id', $group->user_id)
                ->where('status', $group->status)
                ->where(function (Builder $query) use ($group): void {
                    $group->leave_type_id === null
                        ? $query->whereNull('leave_type_id')
                        : $query->where('leave_type_id', $group->leave_type_id);
                })
                ->where('approval_status', $group->approval_status)
                ->where(function (Builder $query) use ($group): void {
                    $note = trim((string) $group->note);

                    if ($note === '') {
                        $query->whereNull('note')->orWhere('note', '');

                        return;
                    }

                    $query->where('note', $group->note);
                })
                ->orderBy('date')
                ->get();
        }));

        return $groups;
    }

    private function baseQuery(User $actor, string $statusFilter, string $requestTypeFilter, string $search): Builder
    {
        return Attendance::query()
            ->whereIn('status', Attendance::REQUEST_STATUSES)
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('approval_status', $statusFilter))
            ->when(! $actor->can('manageLeaveApprovals'), fn (Builder $query) => $query->whereIn('user_id', $this->approvalActors->subordinateIds($actor)))
            ->when($requestTypeFilter !== 'all', function (Builder $query) use ($requestTypeFilter): void {
                if (ctype_digit($requestTypeFilter)) {
                    $query->where('leave_type_id', (int) $requestTypeFilter);

                    return;
                }

                $query->where('status', $requestTypeFilter);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('note', 'like', '%'.$search.'%')
                        ->orWhere('rejection_note', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
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

        if ($actor->can('manageLeaveApprovals')) {
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
