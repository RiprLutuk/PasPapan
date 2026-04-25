<?php

namespace App\Contracts;

use App\Models\User;

interface PayrollServiceInterface
{
    /**
     * Calculate payroll for a user for a specific month/year.
     *
     * @param  int  $month
     * @param  int  $year
     * @return array
     */
    public function calculate(User $user, $month, $year);
}
