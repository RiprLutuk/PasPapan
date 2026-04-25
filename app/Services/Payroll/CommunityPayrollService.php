<?php

namespace App\Services\Payroll;

use App\Contracts\PayrollServiceInterface;
use App\Models\User;

class CommunityPayrollService implements PayrollServiceInterface
{
    public function calculate(User $user, $month, $year)
    {
        // Community Edition: Payroll Logic Locked 🔒
        // Return basic structure with zero values or only basic salary
        // This effectively disables automated calculation logic (Overtime, Allowances, Deductions)

        $basicSalary = $user->basic_salary ?? 0;

        return [
            'basic_salary' => $basicSalary,
            'overtime_pay' => 0,
            'allowances' => [],
            'deductions' => [],
            'total_allowance' => 0,
            'total_deduction' => 0,
            'net_salary' => $basicSalary, // Just passing basic salary through
            'details' => ['status' => 'locked_community_edition'],
        ];
    }
}
