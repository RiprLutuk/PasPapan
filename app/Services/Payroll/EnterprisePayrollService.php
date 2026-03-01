<?php

namespace App\Services\Payroll;

use App\Contracts\PayrollServiceInterface;
use App\Models\User;
use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\PayrollComponent;
use Carbon\Carbon;
use App\Services\Enterprise\LicenseGuard;

class EnterprisePayrollService implements PayrollServiceInterface
{
    public function __construct()
    {
        LicenseGuard::check();
    }

    public function calculate(User $user, $month, $year)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Basic Salary
        $basicSalary = $user->basic_salary ?? 0;

        // 2. Overtime Pay
        $overtimePay = $this->calculateOvertime($user, $startDate, $endDate);

        // 3. Components (Allowances & Deductions)
        $components = PayrollComponent::where('is_active', true)->get();

        $allowances = [];
        $deductions = [];
        $details = [];

        foreach ($components as $component) {
            $amount = 0;

            switch ($component->calculation_type) {
                case 'fixed':
                    $amount = $component->amount;
                    break;
                case 'percentage_basic':
                    $amount = $basicSalary * ($component->percentage / 100);
                    break;
                case 'daily_presence':
                    $presenceCount = $this->calculatePresence($user, $startDate, $endDate);
                    $amount = $presenceCount * $component->amount;
                    $details[$component->name . '_count'] = $presenceCount; // Log count for transparency
                    break;
            }

            // Round to 2 decimals
            $amount = round($amount, 2);

            if ($component->type === 'allowance') {
                $allowances[$component->name] = $amount;
            } else {
                $deductions[$component->name] = $amount;
            }

            // Store detail for this component
            $details[$component->name] = [
                'type' => $component->type,
                'calc' => $component->calculation_type,
                'amount' => $amount
            ];
        }

        $totalAllowance = array_sum($allowances);
        $totalDeduction = array_sum($deductions);

        // 4. Kasbon (Cash Advance) Deduction
        $kasbonRecords = \App\Models\CashAdvance::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('payment_month', $month)
            ->where('payment_year', $year)
            ->get();

        $totalKasbon = $kasbonRecords->sum('amount');

        if ($totalKasbon > 0) {
            $deductions['Kasbon'] = $totalKasbon;
            $totalDeduction += $totalKasbon;
            $details['Kasbon'] = [
                'type' => 'deduction',
                'calc' => 'kasbon',
                'amount' => $totalKasbon,
                'count' => $kasbonRecords->count()
            ];
        }

        // 5. Net Salary
        $netSalary = $basicSalary + $overtimePay + $totalAllowance - $totalDeduction;

        return [
            'basic_salary' => $basicSalary,
            'overtime_pay' => $overtimePay,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'details' => $details, // New field for deep transparency
        ];
    }

    protected function calculateOvertime(User $user, $startDate, $endDate)
    {
        // Get approved overtime duration in minutes
        $totalMinutes = Overtime::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('duration');

        // Rate Calculation (Simple: Hourly Rate * Hours)
        // If hourly_rate is not set, derive from basic salary (e.g., / 173)
        $hourlyRate = $user->hourly_rate ?? ($user->basic_salary / 173);

        $hours = $totalMinutes / 60;

        return round($hours * $hourlyRate, 2);
    }

    protected function calculatePresence(User $user, $startDate, $endDate)
    {
        // Simple count of presence days (check-in present)
        // Can be enhanced to check for 'present', 'late', etc. excluding 'absent'
        return Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['present', 'late', 'half_day']) // Assuming these count as present
            ->count();
    }
}
