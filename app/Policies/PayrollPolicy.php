<?php

namespace App\Policies;

use App\Helpers\Editions;
use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return ! Editions::payrollLocked();
    }

    public function viewAdminAny(User $user): bool
    {
        return ! Editions::payrollLocked() && $user->can('viewAdminPayroll');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return ! Editions::payrollLocked()
            && ($user->can('viewAdminPayroll') || $payroll->user_id === $user->id);
    }

    public function download(User $user, Payroll $payroll): bool
    {
        return $this->view($user, $payroll) && $payroll->status === 'paid';
    }
}
