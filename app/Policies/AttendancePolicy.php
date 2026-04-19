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

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin || $attendance->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }
}
