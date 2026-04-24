<?php

use App\Models\Overtime;
use App\Support\OvertimeCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

test('overtime calculator handles same day and cross midnight windows', function () {
    $calculator = new OvertimeCalculator;

    [$sameDayStart, $sameDayEnd] = $calculator->resolveWindow('2026-04-19', '18:00', '20:30');
    [$crossDayStart, $crossDayEnd] = $calculator->resolveWindow('2026-04-19', '23:00', '02:00');

    expect($calculator->durationInMinutes($sameDayStart, $sameDayEnd))->toBe(150)
        ->and($calculator->durationInMinutes($crossDayStart, $crossDayEnd))->toBe(180)
        ->and($crossDayEnd->isSameDay($crossDayStart->copy()->addDay()))->toBeTrue();
});

test('overtime calculator detects overlapping windows including cross midnight records', function () {
    $calculator = new OvertimeCalculator;
    [$start, $end] = $calculator->resolveWindow('2026-04-19', '23:30', '01:30');

    $existing = new Collection([
        new Overtime([
            'date' => Carbon::parse('2026-04-19'),
            'start_time' => Carbon::parse('2026-04-19 22:00:00'),
            'end_time' => Carbon::parse('2026-04-19 00:30:00'),
            'status' => 'approved',
        ]),
    ]);

    expect($calculator->hasOverlap($existing, $start, $end))->toBeTrue()
        ->and($calculator->windowsOverlap(
            Carbon::parse('2026-04-19 18:00:00'),
            Carbon::parse('2026-04-19 20:00:00'),
            Carbon::parse('2026-04-19 20:00:00'),
            Carbon::parse('2026-04-19 22:00:00'),
        ))->toBeFalse();
});
