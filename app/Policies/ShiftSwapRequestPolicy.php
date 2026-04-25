<?php

namespace App\Policies;

use App\Models\ShiftSwapRequest;
use App\Models\User;

class ShiftSwapRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isUser;
    }

    public function view(User $user, ShiftSwapRequest $request): bool
    {
        return $request->user_id === $user->id || $this->managesUser($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, ShiftSwapRequest $request): bool
    {
        return $request->status === ShiftSwapRequest::STATUS_PENDING
            && $this->managesUser($user, $request);
    }

    public function reject(User $user, ShiftSwapRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    protected function managesUser(User $user, ShiftSwapRequest $request): bool
    {
        return $user->subordinates->pluck('id')->contains($request->user_id);
    }
}
