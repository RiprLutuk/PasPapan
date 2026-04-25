<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('viewAdminAttendances');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $attendance->user_id === $user->id
            || $user->can('viewAdminAttendances')
            || $this->canReview($user, $attendance);
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, Attendance $attendance): bool
    {
        return $this->canReview($user, $attendance);
    }

    public function reject(User $user, Attendance $attendance): bool
    {
        return $this->approve($user, $attendance);
    }

    protected function canReview(User $user, Attendance $attendance): bool
    {
        if (! in_array($attendance->status, Attendance::REQUEST_STATUSES, true)) {
            return false;
        }

        if ($user->can('manageLeaveApprovals')) {
            return true;
        }

        return $user->subordinates->contains('id', $attendance->user_id);
    }
}
