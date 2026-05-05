<?php

namespace App\Policies;

use App\Models\HrChecklistTask;
use App\Models\User;

class HrChecklistTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HrChecklistTask $task): bool
    {
        return $user->can('viewHrChecklists')
            || $task->assigned_to === $user->id
            || $task->case?->user_id === $user->id
            || $task->case?->user?->manager_id === $user->id;
    }

    public function update(User $user, HrChecklistTask $task): bool
    {
        return $user->can('manageHrChecklists') || $task->assigned_to === $user->id;
    }
}
