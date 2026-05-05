<?php

namespace App\Policies;

use App\Models\HrChecklistCase;
use App\Models\User;

class HrChecklistCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewHrChecklists');
    }

    public function view(User $user, HrChecklistCase $case): bool
    {
        return $user->can('viewHrChecklists')
            || $case->user_id === $user->id
            || $case->user?->manager_id === $user->id
            || $case->tasks()->where('assigned_to', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('manageHrChecklists');
    }

    public function update(User $user, HrChecklistCase $case): bool
    {
        return $user->can('manageHrChecklists');
    }

    public function cancel(User $user, HrChecklistCase $case): bool
    {
        return $user->can('manageHrChecklists');
    }
}
