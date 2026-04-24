<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('viewAdminAttendanceCorrections');
    }

    public function view(User $user, AttendanceCorrection $correction): bool
    {
        return $correction->user_id === $user->id
            || $user->can('viewAdminAttendanceCorrections')
            || $this->managesUser($user, $correction);
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, AttendanceCorrection $correction): bool
    {
        if ($user->can('manageAttendanceCorrections')) {
            return in_array($correction->status, [
                AttendanceCorrection::STATUS_PENDING,
                AttendanceCorrection::STATUS_PENDING_ADMIN,
            ], true);
        }

        return $correction->status === AttendanceCorrection::STATUS_PENDING
            && $this->managesUser($user, $correction);
    }

    public function reject(User $user, AttendanceCorrection $correction): bool
    {
        return $this->approve($user, $correction);
    }

    protected function managesUser(User $user, AttendanceCorrection $correction): bool
    {
        return $user->subordinates->pluck('id')->contains($correction->user_id);
    }
}
