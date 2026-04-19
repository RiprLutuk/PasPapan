<?php

namespace App\Support;

class LeaveCalculator
{
    public function remainingAnnualQuota(int $annualQuota, int $usedDays): int
    {
        return max(0, $annualQuota - $usedDays);
    }

    public function wouldExceedAnnualQuota(string $status, int $annualQuota, int $usedDays, int $requestedDays): bool
    {
        if ($status !== 'excused') {
            return false;
        }

        return ($usedDays + $requestedDays) > $annualQuota;
    }
}
