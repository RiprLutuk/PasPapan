<?php

use App\Support\LeaveCalculator;

test('leave calculator returns remaining quota without going negative', function () {
    $calculator = new LeaveCalculator;

    expect($calculator->remainingAnnualQuota(12, 5))->toBe(7)
        ->and($calculator->remainingAnnualQuota(12, 12))->toBe(0)
        ->and($calculator->remainingAnnualQuota(12, 15))->toBe(0);
});

test('leave calculator only enforces quota for annual leave requests', function () {
    $calculator = new LeaveCalculator;

    expect($calculator->wouldExceedAnnualQuota('excused', 12, 10, 3))->toBeTrue()
        ->and($calculator->wouldExceedAnnualQuota('excused', 12, 10, 2))->toBeFalse()
        ->and($calculator->wouldExceedAnnualQuota('sick', 12, 12, 30))->toBeFalse();
});
