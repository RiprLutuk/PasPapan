<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;

class OvertimePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isUser;
    }

    public function view(User $user, Overtime $overtime): bool
    {
        return $overtime->user_id === $user->id || $user->can('manageOvertime');
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }
}
