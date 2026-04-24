<?php

namespace App\Policies;

use App\Models\CashAdvance;
use App\Models\User;
use App\Support\CashAdvanceApprovalService;

class CashAdvancePolicy
{
    public function approve(User $user, CashAdvance $cashAdvance): bool
    {
        return app(CashAdvanceApprovalService::class)->canManage($cashAdvance, $user);
    }

    public function reject(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->approve($user, $cashAdvance);
    }

    public function delete(User $user, CashAdvance $cashAdvance): bool
    {
        return $user->can('manageCashAdvances');
    }
}
